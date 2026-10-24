/**
 * METE CLINIC — контент лендинга.
 *
 * Здесь меняются направления, подборка цен и состав команды.
 * Имена и должности специалистов — из утверждённого списка клиники.
 * Фото сотрудников не выдумываются: при пустом photo показывается монограмма.
 *
 * Чтобы добавить фото: положите файл в assets/images/team/ и укажите путь,
 * например photo: "assets/images/team/zhara.jpg". profileUrl ведёт на страницу
 * специалиста в Yclients с профилем и отзывами.
 */
const SITE_CONTENT = {
  services: [
    {
      title: "Лазерная эпиляция",
      description: "Женские и мужские зоны, комплексы и отдельные участки.",
      image: "assets/images/service-laser.svg",
      alt: "",
      url: "" // отдельная ссылка направления, если появится; иначе общая запись
    },
    {
      title: "Косметология лица",
      description: "Чистки, пилинги, уходовые и аппаратные процедуры.",
      image: "assets/images/service-face.svg",
      alt: "",
      url: ""
    },
    {
      title: "Аппаратная коррекция фигуры",
      description: "ЛПЖ-массаж и аппаратные методики по прайсу клиники.",
      image: "assets/images/service-body.svg",
      alt: "",
      url: ""
    },
    {
      title: "Инъекционная косметология",
      description: "Контурная пластика, ботулинотерапия, мезотерапия и другие процедуры.",
      image: "assets/images/service-injection.svg",
      alt: "",
      url: ""
    }
  ],

  prices: [
    {
      name: "Женский комплекс «Лайт № 1»",
      detail: "подмышки + глубокое бикини",
      price: "2 290 ₽"
    },
    {
      name: "Женский комплекс «Лайт № 2»",
      detail: "подмышки + ноги до колен",
      price: "2 490 ₽"
    },
    {
      name: "Женский комплекс «Стандарт»",
      detail: "",
      price: "3 990 ₽"
    },
    {
      name: "Мужской комплекс «Контур бороды»",
      detail: "",
      price: "3 000 ₽"
    },
    {
      name: "ЛПЖ-массаж (1 зона)",
      detail: "",
      price: "1 790 ₽"
    },
    {
      name: "Ультразвуковая чистка лица",
      detail: "",
      price: "3 500 ₽"
    },
    {
      name: "Пилинги из каталога",
      detail: "",
      price: "от 2 500 ₽"
    },
    {
      name: "Первичный приём врача дерматолога-косметолога",
      detail: "",
      price: "3 500 ₽"
    }
  ],

  team: [
    {
      name: "Жарая Анна Сергеевна",
      role: "Врач дерматолог-косметолог",
      initials: "АЖ",
      photo: "",
      profileUrl:
        "https://n1801245.yclients.ru/company/1599669/personal/select-master/master-info/1599669/5948106?o=m5569812"
    },
    {
      name: "Махмутова Дамира Дамировна",
      role: "Косметолог",
      initials: "ДМ",
      photo: "",
      profileUrl:
        "https://n1801245.yclients.ru/company/1599669/personal/select-master/master-info/1599669/5569812?o=m5569812"
    },
    {
      name: "Сотникова Ксения",
      role: "Специалист лазерной эпиляции",
      initials: "КС",
      photo: "",
      profileUrl:
        "https://n1801245.yclients.ru/company/1599669/personal/select-master/master-info/1599669/4775574?o=m5569812"
    },
    {
      name: "Яковлева Екатерина",
      role: "Специалист лазерной эпиляции и аппаратной косметологии",
      initials: "ЕЯ",
      photo: "",
      profileUrl:
        "https://n1801245.yclients.ru/company/1599669/personal/select-master/master-info/1599669/5716740?o=m5569812"
    }
  ]
};

if (typeof window !== "undefined") {
  window.SITE_CONTENT = SITE_CONTENT;
}

if (typeof module !== "undefined" && module.exports) {
  module.exports = SITE_CONTENT;
}
