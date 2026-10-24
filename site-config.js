/**
 * METE CLINIC — единая точка настройки контактов, записи и карты.
 *
 * ВАЖНО: перед публикацией подтвердите у клиники каждое значение.
 * Пустая строка или пустой массив означает «данных нет»: соответствующий
 * элемент на сайте не отображается, а CTA записи ведёт к блоку контактов.
 *
 * Телефон и адрес ниже взяты с публичной карточки организации в Яндекс Картах
 * (https://yandex.ru/maps/org/mete_klinik/51461565607/) и требуют подтверждения.
 */
const SITE_CONFIG = {
  // Прямая ссылка на онлайн-запись Yclients (общая страница записи клиники).
  bookingUrl: "https://n1801245.yclients.ru/company/1599669/personal/menu?o=",

  // Телефон: отображаемый вид и номер для ссылки tel:
  phoneDisplay: "+7 (922) 399-14-46",
  phoneHref: "+79223991446",

  // MAX: прямая ссылка из задания (без tracking-параметров 2ГИС).
  // Показывается в шапке, hero и контактах; в футере MAX больше нет.
  maxUrl: "https://max.ru/u/f9LHodD0cOJpLifZEh6TFYIMu_rolVQOTrApxpIofPMF6MgW25Htu44yCXU",

  // Адрес и график работы (график пока не подтверждён — строка скрыта)
  address: "ул. Фармана Салманова, 18, Тюмень",
  workingHours: "",

  // Карта: только URL из официального кода встраивания (iframe src), не HTML
  mapProvider: "yandex", // "yandex" | "2gis" | ""
  // Официальный iframe-виджет. Координаты и POI — из карточки клиники;
  // масштаб z=17 выставлен для обзорности (в исходном коде было z=20).
  mapEmbedUrl:
    "https://yandex.ru/map-widget/v1/?ll=65.499822%2C57.120144&mode=poi&poi%5Bpoint%5D=65.499677%2C57.120325&poi%5Buri%5D=ymapsbm1%3A%2F%2Forg%3Foid%3D51461565607&utm_source=share&z=17",
  yandexMapsUrl: "https://yandex.ru/maps/org/mete_klinik/51461565607/",
  twoGisUrl: "",

  // Официальный виджет отзывов Яндекс Карт (iframe src) и ссылка на карточку
  reviewsEmbedUrl: "https://yandex.ru/maps-reviews-widget/51461565607?comments",
  reviewsUrl: "https://yandex.ru/maps/org/mete_klinik/51461565607/",

  // Реальные аккаунты. Пример структуры:
  // { name: "Telegram", url: "https://t.me/...", label: "Написать в Telegram" }
  messengers: [],

  // Соцсети для футера. MAX вынесен в maxUrl и в футере не выводится.
  socialLinks: []
};

if (typeof window !== "undefined") {
  window.SITE_CONFIG = SITE_CONFIG;
}

if (typeof module !== "undefined" && module.exports) {
  module.exports = SITE_CONFIG;
}
