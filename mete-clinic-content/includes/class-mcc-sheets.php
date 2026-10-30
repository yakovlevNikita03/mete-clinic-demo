<?php
/**
 * CSV and XLSX reading and writing without external libraries.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimal spreadsheet support for the price import and export.
 *
 * CSV работает всегда. XLSX читается и пишется встроенными ZipArchive и
 * SimpleXML, поэтому Composer и сторонние библиотеки не нужны. Если на
 * сервере нет расширения zip, остаётся CSV.
 */
class MCC_Sheets {

	/**
	 * Maximum upload size in bytes.
	 */
	const MAX_BYTES = 5242880;

	/**
	 * Maximum size of one unpacked XML part in bytes.
	 */
	const MAX_XML_BYTES = 20971520;

	/**
	 * XML namespace of the spreadsheet format.
	 */
	const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

	/**
	 * Whether XLSX support is available on this server.
	 *
	 * @return bool
	 */
	public static function xlsx_available() {
		return class_exists( 'ZipArchive' ) && class_exists( 'SimpleXMLElement' );
	}

	/**
	 * Build a CSV document (UTF-8 with BOM, ";" by default).
	 *
	 * @param array  $rows      Rows of scalar values.
	 * @param string $delimiter Field delimiter.
	 * @return string
	 */
	public static function csv_build( array $rows, $delimiter = ';' ) {
		$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- in-memory stream.

		if ( false === $handle ) {
			return '';
		}

		foreach ( $rows as $row ) {
			fputcsv( $handle, array_map( array( __CLASS__, 'csv_value' ), (array) $row ), $delimiter, '"', '\\' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv -- in-memory stream.
		}

		rewind( $handle );
		$csv = stream_get_contents( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_stream_get_contents -- in-memory stream.
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- in-memory stream.

		return "\xEF\xBB\xBF" . (string) $csv;
	}

	/**
	 * Convert one CSV cell to a string.
	 *
	 * @param mixed $value Cell value.
	 * @return string
	 */
	private static function csv_value( $value ) {
		if ( null === $value ) {
			return '';
		}

		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}

		return (string) $value;
	}

	/**
	 * Parse a CSV document into rows.
	 *
	 * Поддерживаются BOM, UTF-16, Windows-1251 и разделители «;» и «,».
	 * Полностью пустые строки пропускаются.
	 *
	 * @param string $raw Raw file contents.
	 * @return array
	 */
	public static function csv_parse( $raw ) {
		$raw = (string) $raw;

		if ( '' === $raw ) {
			return array();
		}

		if ( "\xEF\xBB\xBF" === substr( $raw, 0, 3 ) ) {
			$raw = substr( $raw, 3 );
		}

		if ( "\xFF\xFE" === substr( $raw, 0, 2 ) ) {
			$raw = mb_convert_encoding( substr( $raw, 2 ), 'UTF-8', 'UTF-16LE' );
		} elseif ( "\xFE\xFF" === substr( $raw, 0, 2 ) ) {
			$raw = mb_convert_encoding( substr( $raw, 2 ), 'UTF-8', 'UTF-16BE' );
		} elseif ( ! mb_check_encoding( $raw, 'UTF-8' ) ) {
			$raw = mb_convert_encoding( $raw, 'UTF-8', 'Windows-1251' );
		}

		$delimiter = self::detect_delimiter( $raw );
		$handle    = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- in-memory stream.

		if ( false === $handle ) {
			return array();
		}

		fwrite( $handle, $raw ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- in-memory stream.
		rewind( $handle );

		$rows = array();

		while ( false !== ( $row = fgetcsv( $handle, 0, $delimiter, '"', '\\' ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgetcsv -- in-memory stream.
			$row = array_map( array( __CLASS__, 'trim_cell' ), (array) $row );
			$row = array_map( array( __CLASS__, 'strip_nulls' ), $row );

			if ( self::is_empty_row( $row ) ) {
				continue;
			}

			$rows[] = $row;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- in-memory stream.

		return $rows;
	}

	/**
	 * Detect the delimiter of the first line outside quotes.
	 *
	 * @param string $raw Raw contents.
	 * @return string
	 */
	private static function detect_delimiter( $raw ) {
		$line    = strtok( $raw, "\r\n" );
		$line    = false === $line ? '' : $line;
		$semi    = 0;
		$comma   = 0;
		$tab     = 0;
		$in_quot = false;
		$length  = mb_strlen( $line, 'UTF-8' );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = mb_substr( $line, $i, 1, 'UTF-8' );

			if ( '"' === $char ) {
				$in_quot = ! $in_quot;
				continue;
			}

			if ( $in_quot ) {
				continue;
			}

			if ( ';' === $char ) {
				$semi++;
			} elseif ( ',' === $char ) {
				$comma++;
			} elseif ( "\t" === $char ) {
				$tab++;
			}
		}

		if ( $tab > $semi && $tab > $comma ) {
			return "\t";
		}

		return ( $semi >= $comma ) ? ';' : ',';
	}

	/**
	 * Whether a parsed row has no values.
	 *
	 * @param array $row Row.
	 * @return bool
	 */
	private static function is_empty_row( array $row ) {
		foreach ( $row as $cell ) {
			if ( '' !== trim( (string) $cell ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Trim a cell and remove control characters.
	 *
	 * @param mixed $value Cell.
	 * @return string
	 */
	private static function trim_cell( $value ) {
		return trim( (string) $value );
	}

	/**
	 * Remove null bytes from a cell.
	 *
	 * @param mixed $value Cell.
	 * @return string
	 */
	private static function strip_nulls( $value ) {
		return str_replace( "\0", '', (string) $value );
	}

	/**
	 * Build an XLSX document.
	 *
	 * @param array $sheets List of array( 'name' => string, 'rows' => array ).
	 * @return string|false Binary file contents or false on failure.
	 */
	public static function xlsx_build( array $sheets ) {
		if ( ! self::xlsx_available() || empty( $sheets ) ) {
			return false;
		}

		$file = tempnam( sys_get_temp_dir(), 'mccxlsx' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_tempnam -- local temporary file.

		if ( false === $file ) {
			return false;
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $file, ZipArchive::OVERWRITE ) ) {
			@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink -- temporary file.

			return false;
		}

		$zip->addFromString( '[Content_Types].xml', self::content_types( count( $sheets ) ) );
		$zip->addFromString( '_rels/.rels', self::root_rels() );
		$zip->addFromString( 'xl/workbook.xml', self::workbook( $sheets ) );
		$zip->addFromString( 'xl/_rels/workbook.xml.rels', self::workbook_rels( count( $sheets ) ) );
		$zip->addFromString( 'xl/styles.xml', self::styles() );

		$index = 1;

		foreach ( $sheets as $sheet ) {
			$rows = isset( $sheet['rows'] ) && is_array( $sheet['rows'] ) ? $sheet['rows'] : array();

			$zip->addFromString( 'xl/worksheets/sheet' . $index . '.xml', self::worksheet( $rows ) );
			$index++;
		}

		$zip->close();

		$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- temporary local file.
		@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink -- temporary file.

		return false === $contents ? false : $contents;
	}

	/**
	 * Content types part.
	 *
	 * @param int $count Sheets count.
	 * @return string
	 */
	private static function content_types( $count ) {
		$xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
		$xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
		$xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
		$xml .= '<Default Extension="xml" ContentType="application/xml"/>';
		$xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
		$xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';

		for ( $i = 1; $i <= (int) $count; $i++ ) {
			$xml .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
		}

		return $xml . '</Types>';
	}

	/**
	 * Package relationships.
	 *
	 * @return string
	 */
	private static function root_rels() {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
			. '</Relationships>';
	}

	/**
	 * Workbook part with sheet names.
	 *
	 * @param array $sheets Sheets.
	 * @return string
	 */
	private static function workbook( array $sheets ) {
		$xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
		$xml .= '<workbook xmlns="' . self::NS_MAIN . '" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';

		$index = 1;

		foreach ( $sheets as $sheet ) {
			$name = isset( $sheet['name'] ) ? self::sheet_name( (string) $sheet['name'] ) : 'Sheet' . $index;

			$xml .= '<sheet name="' . self::attr( $name ) . '" sheetId="' . $index . '" r:id="rId' . $index . '"/>';
			$index++;
		}

		return $xml . '</sheets></workbook>';
	}

	/**
	 * Workbook relationships.
	 *
	 * @param int $count Sheets count.
	 * @return string
	 */
	private static function workbook_rels( $count ) {
		$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

		for ( $i = 1; $i <= (int) $count; $i++ ) {
			$xml .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
		}

		$xml .= '<Relationship Id="rId' . ( (int) $count + 1 ) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

		return $xml . '</Relationships>';
	}

	/**
	 * Minimal style part expected by Excel.
	 *
	 * @return string
	 */
	private static function styles() {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<styleSheet xmlns="' . self::NS_MAIN . '">'
			. '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
			. '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
			. '<borders count="1"><border/></borders>'
			. '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
			. '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
			. '</styleSheet>';
	}

	/**
	 * Worksheet part.
	 *
	 * @param array $rows Rows.
	 * @return string
	 */
	private static function worksheet( array $rows ) {
		$xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<worksheet xmlns="' . self::NS_MAIN . '"><sheetData>';

		$row_index = 0;

		foreach ( $rows as $row ) {
			$row_index++;
			$xml .= '<row r="' . $row_index . '">';
			$column = 0;

			foreach ( (array) $row as $value ) {
				$cell    = self::cell_ref( $column, $row_index );
				$column++;
				$string  = is_scalar( $value ) ? (string) $value : '';

				if ( '' !== $string && is_numeric( $string ) && ! preg_match( '/^0[0-9]/', $string ) && mb_strlen( $string ) < 16 ) {
					$xml .= '<c r="' . $cell . '"><v>' . self::attr( $string ) . '</v></c>';
					continue;
				}

				if ( '' === $string ) {
					continue;
				}

				$xml .= '<c r="' . $cell . '" t="inlineStr"><is><t xml:space="preserve">' . self::text( $string ) . '</t></is></c>';
			}

			$xml .= '</row>';
		}

		return $xml . '</sheetData></worksheet>';
	}

	/**
	 * Column letters for a zero-based column index.
	 *
	 * @param int $column Zero-based column.
	 * @param int $row    One-based row.
	 * @return string
	 */
	private static function cell_ref( $column, $row ) {
		$letters = '';
		$index   = (int) $column;

		do {
			$letters = chr( 65 + ( $index % 26 ) ) . $letters;
			$index   = (int) floor( $index / 26 ) - 1;
		} while ( $index >= 0 );

		return $letters . (int) $row;
	}

	/**
	 * Sanitize a sheet name for Excel.
	 *
	 * @param string $name Requested name.
	 * @return string
	 */
	private static function sheet_name( $name ) {
		$name = preg_replace( '/[\[\]\:\*\?\/\\\\]/', ' ', $name );
		$name = trim( mb_substr( (string) $name, 0, 31 ) );

		return '' === $name ? 'Sheet' : $name;
	}

	/**
	 * Escape XML text.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function text( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8' );
	}

	/**
	 * Escape XML attribute value.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function attr( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8' );
	}

	/**
	 * Read an XLSX file into named sheets.
	 *
	 * @param string $path Local file path.
	 * @return array|false Map of sheet name to rows, or false on failure.
	 */
	public static function xlsx_read( $path ) {
		if ( ! self::xlsx_available() || ! is_readable( $path ) ) {
			return false;
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $path ) ) {
			return false;
		}

		$shared = self::shared_strings( $zip );
		$sheets = self::sheet_parts( $zip );
		$result = array();

		foreach ( $sheets as $name => $part ) {
			$xml = self::read_entry( $zip, $part );

			if ( false === $xml ) {
				continue;
			}

			$result[ $name ] = self::parse_sheet( $xml, $shared );
		}

		$zip->close();

		return empty( $result ) ? false : $result;
	}

	/**
	 * Read one archive entry with a size guard.
	 *
	 * @param ZipArchive $zip  Archive.
	 * @param string     $part Entry name.
	 * @return string|false
	 */
	private static function read_entry( ZipArchive $zip, $part ) {
		$stat = $zip->statName( $part );

		if ( ! is_array( $stat ) || (int) $stat['size'] > self::MAX_XML_BYTES ) {
			return false;
		}

		$contents = $zip->getFromName( $part );

		return false === $contents ? false : (string) $contents;
	}

	/**
	 * Sheet name to part path map.
	 *
	 * @param ZipArchive $zip Archive.
	 * @return array
	 */
	private static function sheet_parts( ZipArchive $zip ) {
		$workbook = self::read_entry( $zip, 'xl/workbook.xml' );
		$rels     = self::read_entry( $zip, 'xl/_rels/workbook.xml.rels' );
		$targets  = array();

		if ( false !== $rels ) {
			$rel_xml = self::load_xml( $rels );

			if ( $rel_xml instanceof SimpleXMLElement ) {
				foreach ( $rel_xml->Relationship as $relationship ) {
					$id       = (string) $relationship['Id'];
					$target   = (string) $relationship['Target'];
					$target   = ltrim( str_replace( '\\', '/', $target ), '/' );

					if ( 0 === strpos( $target, 'xl/' ) ) {
						$path = $target;
					} else {
						$path = 'xl/' . $target;
					}

					$targets[ $id ] = $path;
				}
			}
		}

		$parts = array();

		if ( false !== $workbook ) {
			$book = self::load_xml( $workbook );

			if ( $book instanceof SimpleXMLElement ) {
				$book->registerXPathNamespace( 'm', self::NS_MAIN );
				$book->registerXPathNamespace( 'r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships' );

				$nodes = $book->xpath( '//m:sheets/m:sheet' );

				if ( is_array( $nodes ) ) {
					$index = 1;

					foreach ( $nodes as $node ) {
						$name = (string) $node['name'];
						$rid  = (string) $node->attributes( 'http://schemas.openxmlformats.org/officeDocument/2006/relationships' )->id;

						$part = isset( $targets[ $rid ] ) ? $targets[ $rid ] : 'xl/worksheets/sheet' . $index . '.xml';

						$parts[ '' !== $name ? $name : 'Sheet' . $index ] = $part;
						$index++;
					}
				}
			}
		}

		if ( empty( $parts ) ) {
			$parts['Sheet1'] = 'xl/worksheets/sheet1.xml';
		}

		return $parts;
	}

	/**
	 * Parse shared strings.
	 *
	 * @param ZipArchive $zip Archive.
	 * @return array
	 */
	private static function shared_strings( ZipArchive $zip ) {
		$raw = self::read_entry( $zip, 'xl/sharedStrings.xml' );

		if ( false === $raw ) {
			return array();
		}

		$xml = self::load_xml( $raw );

		if ( ! $xml instanceof SimpleXMLElement ) {
			return array();
		}

		$xml->registerXPathNamespace( 'm', self::NS_MAIN );
		$nodes = $xml->xpath( '//m:si' );
		$items = array();

		if ( ! is_array( $nodes ) ) {
			return array();
		}

		foreach ( $nodes as $node ) {
			$items[] = self::node_text( $node );
		}

		return $items;
	}

	/**
	 * Text of a shared string node, including rich text runs.
	 *
	 * @param SimpleXMLElement $node Node.
	 * @return string
	 */
	private static function node_text( SimpleXMLElement $node ) {
		$node->registerXPathNamespace( 'm', self::NS_MAIN );
		$parts = $node->xpath( './/m:t' );

		if ( ! is_array( $parts ) || empty( $parts ) ) {
			return '';
		}

		$text = '';

		foreach ( $parts as $part ) {
			$text .= (string) $part;
		}

		return $text;
	}

	/**
	 * Convert one worksheet XML into rows.
	 *
	 * @param string $xml    Worksheet XML.
	 * @param array  $shared Shared strings.
	 * @return array
	 */
	private static function parse_sheet( $xml, array $shared ) {
		$sheet = self::load_xml( $xml );

		if ( ! $sheet instanceof SimpleXMLElement ) {
			return array();
		}

		$sheet->registerXPathNamespace( 'm', self::NS_MAIN );
		$rows = $sheet->xpath( '//m:sheetData/m:row' );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$result = array();

		foreach ( $rows as $row ) {
			$row->registerXPathNamespace( 'm', self::NS_MAIN );
			$cells = $row->xpath( 'm:c' );
			$line  = array();
			$next  = 0;

			foreach ( (array) $cells as $cell ) {
				$ref     = (string) $cell['r'];
				$column  = self::column_index( $ref );
				$column  = null === $column ? $next : $column;
				$next    = $column + 1;
				$value   = self::cell_value( $cell, $shared );
				$line[ $column ] = $value;
			}

			if ( empty( $line ) ) {
				continue;
			}

			$max   = max( array_keys( $line ) );
			$plain = array();

			for ( $i = 0; $i <= $max; $i++ ) {
				$plain[] = isset( $line[ $i ] ) ? (string) $line[ $i ] : '';
			}

			if ( self::is_empty_row( $plain ) ) {
				continue;
			}

			$result[] = array_map( array( __CLASS__, 'strip_nulls' ), $plain );
		}

		return $result;
	}

	/**
	 * Zero-based column index from a cell reference.
	 *
	 * @param string $ref Cell reference, for example "B7".
	 * @return int|null
	 */
	private static function column_index( $ref ) {
		if ( ! preg_match( '/^([A-Z]+)[0-9]*$/i', (string) $ref, $matches ) ) {
			return null;
		}

		$letters = strtoupper( $matches[1] );
		$index   = 0;

		for ( $i = 0; $i < strlen( $letters ); $i++ ) {
			$index = $index * 26 + ( ord( $letters[ $i ] ) - 64 );
		}

		return $index - 1;
	}

	/**
	 * Value of one cell.
	 *
	 * @param SimpleXMLElement $cell   Cell node.
	 * @param array            $shared Shared strings.
	 * @return string
	 */
	private static function cell_value( SimpleXMLElement $cell, array $shared ) {
		$type = (string) $cell['t'];

		if ( 'inlineStr' === $type ) {
			return self::node_text( $cell );
		}

		$cell->registerXPathNamespace( 'm', self::NS_MAIN );
		$values = $cell->xpath( 'm:v' );
		$value  = is_array( $values ) && isset( $values[0] ) ? (string) $values[0] : '';

		if ( 's' === $type ) {
			$index = (int) $value;

			return isset( $shared[ $index ] ) ? (string) $shared[ $index ] : '';
		}

		if ( 'b' === $type ) {
			return '1' === $value ? '1' : '0';
		}

		return $value;
	}

	/**
	 * Parse an XML string without network access.
	 *
	 * @param string $xml XML.
	 * @return SimpleXMLElement|false
	 */
	private static function load_xml( $xml ) {
		if ( '' === trim( (string) $xml ) ) {
			return false;
		}

		$previous = libxml_use_internal_errors( true );
		$parsed   = simplexml_load_string( (string) $xml, 'SimpleXMLElement', LIBXML_NONET );

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		return false === $parsed ? false : $parsed;
	}
}
