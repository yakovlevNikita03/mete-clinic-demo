/**
 * Сборка ZIP-архивов темы и плагина без PowerShell.
 *
 * Заменяет build-zips.ps1 в средах, где powershell.exe недоступен,
 * но есть Node.js. Структура архивов та же: файлы лежат в корне,
 * внешней папки нет, разделители — «/».
 *
 * Запуск: node tools/build-zips.js
 */

const fs = require( 'fs' );
const path = require( 'path' );
const zlib = require( 'zlib' );

const ROOT = path.join( __dirname, '..' );
const TARGETS = [
	{ dir: 'mete-clinic-theme', zip: 'mete-clinic-theme.zip' },
	{ dir: 'mete-clinic-content', zip: 'mete-clinic-content.zip' },
];

const CRC_TABLE = ( () => {
	const table = new Uint32Array( 256 );

	for ( let i = 0; i < 256; i++ ) {
		let c = i;

		for ( let k = 0; k < 8; k++ ) {
			c = c & 1 ? 0xedb88320 ^ ( c >>> 1 ) : c >>> 1;
		}

		table[ i ] = c >>> 0;
	}

	return table;
} )();

function crc32( buffer ) {
	let crc = 0xffffffff;

	for ( let i = 0; i < buffer.length; i++ ) {
		crc = CRC_TABLE[ ( crc ^ buffer[ i ] ) & 0xff ] ^ ( crc >>> 8 );
	}

	return ( crc ^ 0xffffffff ) >>> 0;
}

function walk( dir, base, acc = [] ) {
	for ( const entry of fs.readdirSync( dir, { withFileTypes: true } ) ) {
		const full = path.join( dir, entry.name );

		if ( entry.isDirectory() ) {
			walk( full, base, acc );
		} else {
			acc.push( path.relative( base, full ).split( path.sep ).join( '/' ) );
		}
	}

	return acc;
}

function dosStamp( date ) {
	const time = ( date.getHours() << 11 ) | ( date.getMinutes() << 5 ) | Math.floor( date.getSeconds() / 2 );
	const day = ( ( date.getFullYear() - 1980 ) << 9 ) | ( ( date.getMonth() + 1 ) << 5 ) | date.getDate();

	return { time, day };
}

function buildZip( sourceDir, zipPath ) {
	const files = walk( sourceDir, sourceDir ).sort();
	const { time, day } = dosStamp( new Date() );
	const parts = [];
	const central = [];
	let offset = 0;

	for ( const relative of files ) {
		const data = fs.readFileSync( path.join( sourceDir, relative ) );
		const name = Buffer.from( relative, 'utf8' );
		const compressed = zlib.deflateRawSync( data, { level: 9 } );
		const crc = crc32( data );

		const local = Buffer.alloc( 30 );
		local.writeUInt32LE( 0x04034b50, 0 );
		local.writeUInt16LE( 20, 4 );
		local.writeUInt16LE( 0x0800, 6 ); // UTF-8 имена.
		local.writeUInt16LE( 8, 8 ); // deflate.
		local.writeUInt16LE( time, 10 );
		local.writeUInt16LE( day, 12 );
		local.writeUInt32LE( crc, 14 );
		local.writeUInt32LE( compressed.length, 18 );
		local.writeUInt32LE( data.length, 22 );
		local.writeUInt16LE( name.length, 26 );
		local.writeUInt16LE( 0, 28 );

		parts.push( local, name, compressed );

		central.push( { name, crc, compressed: compressed.length, size: data.length, offset } );
		offset += local.length + name.length + compressed.length;
	}

	const centralStart = offset;

	for ( const entry of central ) {
		const header = Buffer.alloc( 46 );
		header.writeUInt32LE( 0x02014b50, 0 );
		header.writeUInt16LE( 20, 4 );
		header.writeUInt16LE( 20, 6 );
		header.writeUInt16LE( 0x0800, 8 );
		header.writeUInt16LE( 8, 10 );
		header.writeUInt16LE( time, 12 );
		header.writeUInt16LE( day, 14 );
		header.writeUInt32LE( entry.crc, 16 );
		header.writeUInt32LE( entry.compressed, 20 );
		header.writeUInt32LE( entry.size, 24 );
		header.writeUInt16LE( entry.name.length, 28 );
		header.writeUInt16LE( 0, 30 );
		header.writeUInt16LE( 0, 32 );
		header.writeUInt16LE( 0, 34 );
		header.writeUInt16LE( 0, 36 );
		header.writeUInt32LE( 0, 38 );
		header.writeUInt32LE( entry.offset, 42 );

		parts.push( header, entry.name );
		offset += header.length + entry.name.length;
	}

	const end = Buffer.alloc( 22 );
	end.writeUInt32LE( 0x06054b50, 0 );
	end.writeUInt16LE( 0, 4 );
	end.writeUInt16LE( 0, 6 );
	end.writeUInt16LE( central.length, 8 );
	end.writeUInt16LE( central.length, 10 );
	end.writeUInt32LE( offset - centralStart, 12 );
	end.writeUInt32LE( centralStart, 16 );
	end.writeUInt16LE( 0, 20 );

	parts.push( end );

	fs.writeFileSync( zipPath, Buffer.concat( parts ) );

	return { files: files.length, size: fs.statSync( zipPath ).size };
}

for ( const target of TARGETS ) {
	const sourceDir = path.join( ROOT, target.dir );
	const zipPath = path.join( ROOT, target.zip );
	const result = buildZip( sourceDir, zipPath );

	console.log( target.zip + ': файлов ' + result.files + ', ' + result.size + ' байт' );
}
