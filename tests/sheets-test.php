<?php
/**
 * Автономные проверки CSV/XLSX и сопоставления колонок.
 *
 * Запуск без WordPress:
 *
 *   php tests/sheets-test.php
 *
 * Проверяются только функции, которым не нужна база данных: сборка и разбор
 * файлов, нормализация заголовков, разбор «да/нет» и статусов.
 *
 * @package Mete_Clinic_Content
 */

require __DIR__ . '/lib/wordpress-stubs.php';
require __DIR__ . '/../mete-clinic-content/includes/class-mcc-sheets.php';
require __DIR__ . '/../mete-clinic-content/includes/class-mcc-price-data.php';

$GLOBALS['mcc_test_count']    = 0;
$GLOBALS['mcc_test_failures'] = 0;

/**
 * Assert one condition.
 *
 * @param string $label   Label.
 * @param bool   $ok      Result.
 * @param string $details Extra info.
 * @return void
 */
function mcc_t( $label, $ok, $details = '' ) {
	$GLOBALS['mcc_test_count']++;

	if ( $ok ) {
		echo 'OK   ' . $label . "\n";
		return;
	}

	$GLOBALS['mcc_test_failures']++;
	echo 'FAIL ' . $label . ( '' !== $details ? ' (' . $details . ')' : '' ) . "\n";
}

/**
 * Pad rows to the same width for comparison.
 *
 * @param array $rows Rows.
 * @return array
 */
function mcc_pad( array $rows ) {
	$width = 0;

	foreach ( $rows as $row ) {
		$width = max( $width, count( (array) $row ) );
	}

	$padded = array();

	foreach ( $rows as $row ) {
		$row      = array_map( 'strval', (array) $row );
		$padded[] = array_pad( $row, $width, '' );
	}

	return $padded;
}

echo "MÉTE CLINIC — проверки файлов и импорта\n";

/* CSV ------------------------------------------------------------------- */

$rows = array(
	array( 'Услуги', 'Цена', 'Описание' ),
	array( 'Консультация косметолога', '2000', 'Короткое описание' ),
	array( 'Пилинг; курс', '2 290,50', "Первая строка\nВторая строка" ),
	array( 'Кавычки "внутри"', '', 'Значение с ; и с "кавычками"' ),
);

$csv = MCC_Sheets::csv_build( $rows );

mcc_t( 'CSV начинается с UTF-8 BOM', "\xEF\xBB\xBF" === substr( $csv, 0, 3 ) );
mcc_t( 'CSV содержит разделитель «;»', false !== strpos( $csv, ';' ) );

$parsed = MCC_Sheets::csv_parse( $csv );

mcc_t(
	'CSV читается без потерь',
	mcc_pad( $rows ) === mcc_pad( $parsed ),
	wp_json_encode_stub( mcc_pad( $parsed ) )
);

$comma = MCC_Sheets::csv_parse( "A,B\n1,2\n" );
mcc_t( 'CSV с запятой определяется автоматически', array( array( 'A', 'B' ), array( '1', '2' ) ) === $comma );

$semi_in_value = MCC_Sheets::csv_parse( "Название;Цена\n\"Массаж; курс\";3000\n" );
mcc_t(
	'Точка с запятой внутри кавычек не ломает строку',
	isset( $semi_in_value[1][0] ) && 'Массаж; курс' === $semi_in_value[1][0]
);

$cp1251 = mb_convert_encoding( "Название;Цена\nУслуга;2000\n", 'Windows-1251', 'UTF-8' );
$from_cp = MCC_Sheets::csv_parse( $cp1251 );
mcc_t(
	'Windows-1251 перекодируется в UTF-8',
	isset( $from_cp[1][0] ) && 'Услуга' === $from_cp[1][0],
	isset( $from_cp[1][0] ) ? $from_cp[1][0] : 'нет строки'
);

$utf16 = "\xFF\xFE" . mb_convert_encoding( "Название;Цена\r\nУслуга;2000\r\n", 'UTF-16LE', 'UTF-8' );
$from16 = MCC_Sheets::csv_parse( $utf16 );
mcc_t(
	'UTF-16 из Excel читается',
	isset( $from16[1][1] ) && '2000' === $from16[1][1],
	wp_json_encode_stub( $from16 )
);

mcc_t( 'Пустой файл даёт пустой массив', array() === MCC_Sheets::csv_parse( '' ) );
mcc_t(
	'Пустые строки пропускаются',
	1 === count( MCC_Sheets::csv_parse( "A;B\n\n;\n" ) )
);

/* XLSX ------------------------------------------------------------------ */

if ( ! MCC_Sheets::xlsx_available() ) {
	echo "SKIP XLSX: нет расширения zip или SimpleXML\n";
} else {
	$sheets = array(
		array(
			'name' => 'Услуги',
			'rows' => $rows,
		),
		array(
			'name' => 'Специалисты',
			'rows' => array(
				array( 'ID', 'ФИО', 'Опыт' ),
				array( '7', 'Иванова Мария', '12 лет' ),
			),
		),
		array(
			'name' => 'Категории',
			'rows' => array(
				array( 'ID', 'Слаг', 'Название' ),
				array( '3', 'konsultaciya', 'Консультация' ),
			),
		),
	);

	$binary = MCC_Sheets::xlsx_build( $sheets );

	mcc_t( 'XLSX собирается', is_string( $binary ) && strlen( $binary ) > 500 );
	mcc_t( 'XLSX — это zip-архив', is_string( $binary ) && "PK\x03\x04" === substr( $binary, 0, 4 ) );

	$file = tempnam( sys_get_temp_dir(), 'mcctest' );
	file_put_contents( $file, $binary );

	$read = MCC_Sheets::xlsx_read( $file );

	unlink( $file );

	mcc_t( 'XLSX читается', is_array( $read ) && 3 === count( $read ), is_array( $read ) ? implode( ', ', array_keys( $read ) ) : 'false' );

	if ( is_array( $read ) ) {
		mcc_t( 'Лист «Услуги» на месте', isset( $read['Услуги'] ) );
		mcc_t( 'Лист «Специалисты» на месте', isset( $read['Специалисты'] ) );
		mcc_t( 'Лист «Категории» на месте', isset( $read['Категории'] ) );

		mcc_t(
			'Содержимое листа «Услуги» совпадает',
			isset( $read['Услуги'] ) && mcc_pad( $rows ) === mcc_pad( $read['Услуги'] ),
			isset( $read['Услуги'] ) ? wp_json_encode_stub( mcc_pad( $read['Услуги'] ) ) : 'листа нет'
		);

		mcc_t(
			'Многострочное описание сохраняется',
			isset( $read['Услуги'][2][2] ) && "Первая строка\nВторая строка" === $read['Услуги'][2][2],
			isset( $read['Услуги'][2][2] ) ? str_replace( "\n", '\\n', $read['Услуги'][2][2] ) : 'пусто'
		);
	}

	/* Книга, собранная Excel: строки лежат в sharedStrings. */
	$crafted = mcc_make_shared_xlsx(
		'Услуги',
		array(
			array( array( 's', '0' ), array( 's', '1' ) ),
			array( array( 's', '2' ), array( 'n', '2000' ) ),
		),
		array( 'Название', 'Цена', 'Консультация' )
	);

	$crafted_file = tempnam( sys_get_temp_dir(), 'mccshared' );
	file_put_contents( $crafted_file, $crafted );

	$shared_read = MCC_Sheets::xlsx_read( $crafted_file );

	unlink( $crafted_file );

	mcc_t(
		'XLSX с sharedStrings читается',
		is_array( $shared_read )
			&& isset( $shared_read['Услуги'][0][0] )
			&& 'Название' === $shared_read['Услуги'][0][0]
			&& isset( $shared_read['Услуги'][1][1] )
			&& '2000' === $shared_read['Услуги'][1][1],
		wp_json_encode_stub( $shared_read )
	);
}

/* Заголовки ------------------------------------------------------------- */

$service_header = array(
	'ID',
	'Категория',
	'Название',
	'Тип цены',
	'Цена',
	'Цена до',
	'Примечание к цене',
	'Минут',
	'На главной (да/нет)',
	'Порядок на главной',
	'Статус (опубликована/скрыта)',
	'Ссылка записи',
	'Описание',
);

$mapping = MCC_Price_Data::map_headers( MCC_Price_Data::SERVICE, $service_header );

mcc_t( 'Шаблон услуг распознаётся полностью', empty( $mapping['missing'] ) && empty( $mapping['unknown'] ), wp_json_encode_stub( $mapping ) );
mcc_t( 'Все 13 колонок услуг сопоставлены', 13 === count( $mapping['map'] ) );

$partial = MCC_Price_Data::map_headers(
	MCC_Price_Data::SERVICE,
	array( 'Название', 'Цена', 'Поставщик' )
);

mcc_t( 'Обязательная колонка «Название» найдена', empty( $partial['missing'] ) );
mcc_t( 'Неизвестная колонка не ломает импорт', array( 'Поставщик' ) === array_values( $partial['unknown'] ) );

$no_name = MCC_Price_Data::map_headers( MCC_Price_Data::CATEGORY, array( 'Слаг', 'Порядок' ) );

mcc_t( 'Без названия категория не импортируется', array( 'name' ) === $no_name['missing'] );

mcc_t(
	'Скобки в заголовке не мешают',
	'на главной' === MCC_Price_Data::normalize_header( 'На главной (да/нет)' )
);

mcc_t(
	'Регистр и звёздочки в заголовке не мешают',
	'цена' === MCC_Price_Data::normalize_header( '  ЦЕНА*: ' )
);

/* Значения -------------------------------------------------------------- */

mcc_t( '«да» — это 1', 1 === MCC_Price_Data::parse_flag( 'да' ) );
mcc_t( '«Нет» — это 0', 0 === MCC_Price_Data::parse_flag( 'Нет' ) );
mcc_t( 'Мусор в «На главной» не проходит', null === MCC_Price_Data::parse_flag( 'иногда' ) );

mcc_t( '«опубликована» — publish', 'publish' === MCC_Price_Data::parse_status( 'Опубликована' ) );
mcc_t( '«скрыт» — draft', 'draft' === MCC_Price_Data::parse_status( 'скрыт' ) );
mcc_t( 'Пустой статус не проходит', null === MCC_Price_Data::parse_status( '' ) );

/* Пример и подсказки ---------------------------------------------------- */

foreach ( array( MCC_Price_Data::SERVICE, MCC_Price_Data::SPECIALIST, MCC_Price_Data::CATEGORY ) as $entity ) {
	$header  = MCC_Price_Data::header_row( $entity );
	$example = MCC_Price_Data::example_row( $entity );
	$help    = MCC_Price_Data::help_rows( $entity );

	mcc_t( 'Шапка и пример совпадают по ширине: ' . $entity, count( $header ) === count( $example ) );
	mcc_t( 'В подсказках есть заголовок: ' . $entity, count( $help ) > 2 );
}

echo "\n";
echo 'Проверок: ' . (int) $GLOBALS['mcc_test_count'] . ', ошибок: ' . (int) $GLOBALS['mcc_test_failures'] . "\n";

if ( (int) $GLOBALS['mcc_test_failures'] > 0 ) {
	exit( 1 );
}

/**
 * JSON helper that works without WordPress.
 *
 * @param mixed $value Value.
 * @return string
 */
function wp_json_encode_stub( $value ) {
	$json = json_encode( $value, JSON_UNESCAPED_UNICODE );

	return false === $json ? '' : $json;
}

/**
 * Build a small XLSX that stores strings in sharedStrings.xml.
 *
 * @param string $sheet_name Sheet name.
 * @param array  $cells      Rows of array( type, value ).
 * @param array  $shared     Shared strings.
 * @return string
 */
function mcc_make_shared_xlsx( $sheet_name, array $cells, array $shared ) {
	$file = tempnam( sys_get_temp_dir(), 'mcccraft' );
	$zip  = new ZipArchive();

	$zip->open( $file, ZipArchive::OVERWRITE );

	$zip->addFromString(
		'[Content_Types].xml',
		'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
		. '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
		. '<Default Extension="xml" ContentType="application/xml"/>'
		. '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
		. '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
		. '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
		. '</Types>'
	);

	$zip->addFromString(
		'_rels/.rels',
		'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
		. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
		. '</Relationships>'
	);

	$zip->addFromString(
		'xl/workbook.xml',
		'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
		. '<sheets><sheet name="' . htmlspecialchars( $sheet_name, ENT_QUOTES | ENT_XML1, 'UTF-8' ) . '" sheetId="1" r:id="rId1"/></sheets>'
		. '</workbook>'
	);

	$zip->addFromString(
		'xl/_rels/workbook.xml.rels',
		'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
		. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
		. '</Relationships>'
	);

	$strings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count( $shared ) . '" uniqueCount="' . count( $shared ) . '">';

	foreach ( $shared as $string ) {
		$strings .= '<si><t>' . htmlspecialchars( (string) $string, ENT_QUOTES | ENT_XML1, 'UTF-8' ) . '</t></si>';
	}

	$strings .= '</sst>';

	$zip->addFromString( 'xl/sharedStrings.xml', $strings );

	$sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

	$row_number = 1;

	foreach ( $cells as $row ) {
		$sheet .= '<row r="' . $row_number . '">';
		$column = 0;

		foreach ( $row as $cell ) {
			$ref    = chr( 65 + $column ) . $row_number;
			$column++;

			if ( 's' === $cell[0] ) {
				$sheet .= '<c r="' . $ref . '" t="s"><v>' . (int) $cell[1] . '</v></c>';
			} else {
				$sheet .= '<c r="' . $ref . '"><v>' . htmlspecialchars( (string) $cell[1], ENT_QUOTES | ENT_XML1, 'UTF-8' ) . '</v></c>';
			}
		}

		$sheet .= '</row>';
		$row_number++;
	}

	$sheet .= '</sheetData></worksheet>';

	$zip->addFromString( 'xl/worksheets/sheet1.xml', $sheet );
	$zip->close();

	$contents = file_get_contents( $file );

	unlink( $file );

	return (string) $contents;
}
