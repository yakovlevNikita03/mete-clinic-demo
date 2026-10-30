/**
 * Собирает каталог из частей и проверяет данные.
 *
 * Запуск: node tools/build-catalog.js
 * Результат: data/catalog.json
 */

const fs = require( 'fs' );
const path = require( 'path' );

const root = path.join( __dirname, '..', 'data' );
const parts = [ '01-consult-epilation.json', '02-cosmetology.json' ];

const categories = [];
const problems = [];

for ( const part of parts ) {
	const file = path.join( root, part );
	const list = JSON.parse( fs.readFileSync( file, 'utf8' ) );

	for ( const category of list ) {
		categories.push( category );
	}
}

const seenSlugs = new Set();
let total = 0;

for ( const category of categories ) {
	if ( seenSlugs.has( category.slug ) ) {
		problems.push( 'Дубль слага категории: ' + category.slug );
	}
	seenSlugs.add( category.slug );

	const titles = new Set();

	for ( const service of category.services ) {
		total++;

		if ( titles.has( service.title ) ) {
			problems.push( 'Дубль услуги в «' + category.name + '»: ' + service.title );
		}
		titles.add( service.title );

		if ( ! Number.isInteger( service.minutes ) || service.minutes < 1 || service.minutes > 1440 ) {
			problems.push( 'Некорректная длительность: ' + category.name + ' / ' + service.title );
		}

		if ( [ 'fixed', 'from', 'range', 'free' ].indexOf( service.type ) === -1 ) {
			problems.push( 'Неизвестный тип цены: ' + category.name + ' / ' + service.title );
		}

		if ( service.type !== 'free' && ! ( service.price > 0 ) ) {
			problems.push( 'Нет цены у платной услуги: ' + category.name + ' / ' + service.title );
		}

		if ( service.type === 'free' && service.price !== 0 ) {
			problems.push( 'У бесплатной услуги указана цена: ' + category.name + ' / ' + service.title );
		}
	}
}

categories.sort( ( a, b ) => a.order - b.order );

const outDir = path.join( __dirname, '..', 'mete-clinic-content', 'data' );
fs.mkdirSync( outDir, { recursive: true } );

fs.writeFileSync(
	path.join( outDir, 'catalog.json' ),
	JSON.stringify( categories, null, 2 ) + '\n',
	'utf8'
);

console.log( 'Категорий: ' + categories.length + ', услуг: ' + total );

for ( const category of categories ) {
	console.log( '  ' + category.name + ': ' + category.services.length );
}

if ( problems.length ) {
	console.log( '\nПроблемы:' );
	for ( const problem of problems ) {
		console.log( '  - ' + problem );
	}
	process.exit( 1 );
}

console.log( '\nПроблем нет.' );
