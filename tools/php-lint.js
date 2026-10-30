/**
 * Минимальная проверка синтаксиса PHP без PHP CLI.
 *
 * Использует пакет php-parser (npm). Путь ищется по порядку:
 *   1. переменная окружения MCC_PHP_PARSER;
 *   2. локальный node_modules рядом с этим скриптом;
 *   3. временная папка php-lint в профиле пользователя.
 *
 * Запуск:
 *   node tools/php-lint.js <каталог>
 *
 * Это не замена `php -l`: ловит синтаксис, но не проверяет типы,
 * несуществующие функции и ошибки времени выполнения.
 */

const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

function resolveParser() {
	const candidates = [
		process.env.MCC_PHP_PARSER,
		path.join( __dirname, '..', 'node_modules', 'php-parser', 'src', 'index.js' ),
		path.join( os.tmpdir(), 'php-lint', 'node_modules', 'php-parser', 'src', 'index.js' ),
		'C:/Users/nyakovlev/AppData/Local/Temp/php-lint/package/src/index.js',
	].filter( Boolean );

	for ( const candidate of candidates ) {
		if ( fs.existsSync( candidate ) ) {
			return candidate;
		}
	}

	console.error( 'Не найден php-parser. Установите его: npm install php-parser' );
	console.error( 'или укажите путь в MCC_PHP_PARSER.' );
	process.exit( 2 );
}

const engine = require( resolveParser() );
const parser = new engine( {
	parser: { extractDoc: false, php7: true, suppressErrors: false },
	ast: { withPositions: true },
	lexer: { all_tokens: false, comment_tokens: false },
} );

function walk( dir, acc = [] ) {
	for ( const entry of fs.readdirSync( dir, { withFileTypes: true } ) ) {
		const full = path.join( dir, entry.name );
		if ( entry.isDirectory() ) {
			walk( full, acc );
		} else if ( entry.name.endsWith( '.php' ) ) {
			acc.push( full );
		}
	}
	return acc;
}

const root = process.argv[ 2 ];
if ( ! root ) {
	console.error( 'Укажите каталог: node tools/php-lint.js <dir>' );
	process.exit( 2 );
}

const files = walk( root );
let failed = 0;

for ( const file of files ) {
	try {
		parser.parseCode( fs.readFileSync( file, 'utf8' ), file );
	} catch ( error ) {
		failed++;
		const line = error.lineNumber ? ':' + error.lineNumber : '';
		console.log( 'FAIL ' + path.relative( root, file ) + line );
		console.log( '     ' + String( error.message ).split( '\n' )[ 0 ] );
	}
}

console.log( 'Файлов: ' + files.length + ', ошибок: ' + failed );
process.exit( failed ? 1 : 0 );
