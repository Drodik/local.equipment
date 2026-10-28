# Модуль «Оборудование» (`local.equipment`)

Модуль Битрикс24 для тестового задания (`docs/spec/test-task.md`). Что в нём есть:

- HL-блок «Оборудование» и тестовые данные;
- смарт-процесс «Заявка на оборудование», связанный со сделкой;
- грид оборудования на `main.ui.grid` и `main.ui.filter`;
- вкладка «Оборудование» в карточке смарт-процесса;
- счётчик оборудования под полем «Клиент» в карточке сделки.

## 1. Структура репозитория

Корень репозитория равен `DOCUMENT_ROOT` портала.

```
composer.json, phpunit.xml.dist       # sprint.migration ставится composer'ом в local/modules/
local/modules/local.equipment/
  include.php                         # PSR-4 автозагрузка lib/ (Loader::registerNamespace)
  install/index.php                   # инсталлятор: события + копирование компонента и JS
  install/components/local/equipment.grid/   # → /local/components/local/equipment.grid
  install/js/local/equipment/deal-count/     # → /local/js/local/equipment/deal-count
  config/migrations.equipment.php     # конфиг sprint.migration «equipment»
  migrations/Version2026102800000{1..4}.php
  lib/                                # классы Local\Equipment\* (PascalCase, PSR-4)
  lang/ru/...
  tests/                              # PHPUnit для чистых классов
```

## 2. Установка

1. `composer install`. Composer ставит `andreyryabin/sprint.migration` в `local/modules/sprint.migration`, путь задаёт `composer/installers`.
2. В админке открыть «Marketplace → Установленные решения» и установить сначала `sprint.migration`, затем `local.equipment`. Инсталлятор регистрирует обработчики событий и копирует компонент и JS-расширение в `/local/components` и `/local/js`.
3. Применить миграции:

   ```
   php local/modules/sprint.migration/tools/migrate.php up --config=equipment
   ```

   | Миграция | Что делает |
   |---|---|
   | `…0001` | HL-блок `Equipment` (`b_hlbd_equipment`) с полями `UF_NAME`, `UF_TYPE` (список: Роутер / Ноутбук / Монитор), `UF_CREATED_TIME`, `UF_DEAL_ID` |
   | `…0002` | смарт-процесс «Заявка на оборудование», сделка привязана к нему как родитель; `entityTypeId` сохраняется в опцию `local.equipment:smart_process_entity_type_id` |
   | `…0003` | две тестовые сделки, элемент смарт-процесса, привязанный к первой из них, и 7 записей оборудования: 5 для сделки A, 2 для сделки B |
   | `…0004` | группа пользователей `EQUIPMENT_COUNT_HIGHLIGHT` «Выделение счётчика оборудования» и добавление в неё пользователя с ID 5 (если его нет, миграция выводит warning) |

   Откат делается командой `down` в обратном порядке. Удаление модуля данные не трогает: их удаляют только `down` миграций. Делать `down` нужно до удаления модуля: после удаления sprint.migration не видит конфиг `equipment`.

Тесты (Docker):

```
docker run --rm -v "$PWD":/app -w /app php:8.4-cli vendor/bin/phpunit
```

## 3. Где код каждого пункта ТЗ

| Пункт ТЗ | Код |
|---|---|
| 1. HL-блок и тестовые записи | `migrations/Version20261028000001.php`, `migrations/Version20261028000003.php` |
| 2. Смарт-процесс, связанный со сделкой, тестовая сделка и элемент | `migrations/Version20261028000002.php`, `migrations/Version20261028000003.php`, `lib/Crm/SmartProcess.php` |
| 3. Компонент (`main.ui.grid` + `main.ui.filter`) | `install/components/local/equipment.grid/class.php`, `templates/.default/template.php`, `lib/Repository/EquipmentRepository.php`, `lib/Grid/FilterMapper.php` |
| 4. Вкладка «Оборудование» в карточке смарт-процесса | `lib/Crm/DetailTabHandler.php` (компонент `local:equipment.grid` рендерится в `html` вкладки) |
| 5. Количество оборудования под полем «Клиент» | `lib/Crm/DealCountHandler.php`, `lib/Crm/DealPage.php`, `lib/Crm/DealCountViewDTO.php`, `lib/Access/HighlightPolicy.php`, `lib/Access/HighlightGroup.php`, `install/js/local/equipment/deal-count/` |

Все пути указаны относительно `local/modules/local.equipment/`.

## 4. Как подключается скрипт счётчика

1. `install/index.php` (`installEvents`) регистрирует обработчик `main:OnEpilog` → `Local\Equipment\Crm\DealCountHandler::onEpilog`. Тот же метод копирует (`installFiles`) расширение в `/local/js/local/equipment/deal-count/`.
2. `DealCountHandler::onEpilog` срабатывает на каждой публичной странице и сразу выходит, если URL не `/crm/deal/details/<id>/` (`DealPage::extractDealId`). Карточка в слайдере — отдельная страница с `IFRAME=Y`, поэтому событие срабатывает и там.
3. На странице сделки обработчик проверяет право чтения сделки и считает записи HL-блока с `UF_DEAL_ID = id` (`EquipmentRepository::countByDeal`). Затем он решает, выделять ли блок: пользователь должен состоять в группе `EQUIPMENT_COUNT_HIGHLIGHT` (`HighlightPolicy`, `HighlightGroup`). Членство читается из `b_user_group` с учётом дат активности, а не из сессии, поэтому выделение появляется без перелогина. Если HL-блока нет или запрос упал, обработчик пишет в журнал событий и выходит: карточка открывается без счётчика.
4. `Extension::load('local.equipment.deal-count')` подключает `script.js`, `style.css` и фразы из `lang/ru/config.php`. Ядро ищет расширение `local.equipment.deal-count` в `/local/js/local/equipment/deal-count/config.php`.
5. `Asset::addString` добавляет inline-вызов `BX.ready(() => BX.Local.Equipment.DealCount.init({dealId, count, highlight}))`.
6. `script.js` находит в редакторе блок поля `[data-cid="CLIENT"]` и вставляет после него блок «Оборудование по сделке: N». В блоке нет input, `contenteditable` и обработчиков. При `highlight = true` добавляется модификатор `--highlight` (рамка и фон). Редактор перерисовывает поля при переключении просмотра и редактирования, поэтому `MutationObserver` возвращает блок на место. Вставка идемпотентна: блок помечен `data-local-equipment`.

## 5. Обоснование решений

- **Миграции внутри модуля.** Модуль отдаёт свой конфиг через событие `sprint.migration:OnSearchConfigFiles` (`ConfigHandler`). Миграции, конфиг и код лежат вместе и ставятся одной командой `--config=equipment`, а отдельная таблица `sprint_migration_equipment` не смешивает их с миграциями проекта.
- **Вкладка рендерится сразу (`html`), а не лениво (`loader`).** `main.ui.grid` и `main.ui.filter` в `AJAX_MODE` перезагружаются запросом к текущей странице, то есть к карточке. Поэтому компонент должен быть в её выдаче, как у штатной вкладки товаров сделки. При ленивой загрузке через отдельный эндпоинт фильтр, сортировка и пагинация не находили бы грид в ответе. Цена решения — запрос к HL-блоку при каждом открытии карточки (одна страница записей одной сделки). `DEAL_ID` вычисляет сервер из `PARENT_ID_2` элемента, с клиента он не приходит, поэтому подставить чужую сделку нельзя. Компонент дополнительно проверяет право чтения сделки. Если сделка не привязана, компонент пишет «Сделка не привязана» и не строит грид: пустой фильтр показал бы всё оборудование. Ошибки вкладки не ломают карточку.
- **Счётчик через JS-расширение, а не через пользовательское поле.** Число вычисляется из HL-блока. В UF его пришлось бы синхронизировать при каждом изменении записей. Кроме того, позиция UF в карточке зависит от личных настроек редактора каждого пользователя, а требования «под полем Клиент» и «неинтерактивное» JS-вставка выполняет напрямую. Число и решение о выделении считает сервер во время рендера, поэтому лишнего AJAX-запроса нет, а правило выделения не живёт в JS.
- **Смарт-процесс ищется по опции модуля, а не по CODE.** У типа смарт-процесса нет поля CODE, а `entityTypeId` на каждом портале свой. Миграция 2 сохраняет его в опцию, остальной код читает её через `SmartProcess::getEntityTypeId()`.
- **Выделение по группе пользователей вместо зашитого ID 5.** Кому выделять, меняется в админке без правки кода. ID 5 встречается только в миграции 4 как тестовые данные из ТЗ.
- **Запросы через `Query` с `where()` и `fetchAll()`.** ORM-объекты и коллекции не используются, из БД данные приходят массивами (`fetchAll`). Дальше по коду их несут readonly DTO: запись HL-блока — `EquipmentItemDTO::fromRow()`, данные счётчика для JS — `DealCountViewDTO` (`JsonSerializable`). Опечатка в ключе массива молча дала бы пустое значение, а с DTO такая ошибка сразу падает. Фильтр грида переводится в условия `[column, operator, value]` (`FilterMapper::toConditions`) и применяется через `ConditionTree::where()`. Спецсимволы `%` и `_` в поиске экранируются.