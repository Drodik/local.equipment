(function () {
	'use strict';

	BX.namespace('BX.Local.Equipment');

	var BLOCK_ATTR = 'data-local-equipment';
	var CLIENT_SELECTOR = '[data-cid="CLIENT"]';

	/**
	 * Неинтерактивный блок «Оборудование по сделке» под полем «Клиент» в карточке сделки.
	 * Число и признак выделения считает сервер (DealCountHandler), здесь только отрисовка.
	 */
	BX.Local.Equipment.DealCount = {
		options: null,
		observer: null,
		scheduled: false,

		/**
		 * @param {{dealId: number, count: number, highlight: boolean}} options
		 */
		init: function (options) {
			this.options = {
				dealId: parseInt(options.dealId, 10) || 0,
				count: parseInt(options.count, 10) || 0,
				highlight: options.highlight === true
			};

			this.ensure();

			// Редактор перерисовывает поля при смене режима просмотр/редактирование —
			// возвращаем блок на место, если он пропал.
			if (!this.observer && window.MutationObserver) {
				this.observer = new MutationObserver(this.schedule.bind(this));
				this.observer.observe(document.body, { childList: true, subtree: true });
			}
		},

		schedule: function () {
			if (this.scheduled) {
				return;
			}
			this.scheduled = true;
			window.requestAnimationFrame(function () {
				this.scheduled = false;
				this.ensure();
			}.bind(this));
		},

		ensure: function () {
			var client = document.querySelector(CLIENT_SELECTOR);
			if (!client) {
				return;
			}

			var next = client.nextElementSibling;
			if (next && next.hasAttribute(BLOCK_ATTR)) {
				return;
			}

			var stale = document.querySelector('[' + BLOCK_ATTR + ']');
			if (stale) {
				stale.parentNode.removeChild(stale);
			}

			client.parentNode.insertBefore(this.render(), client.nextSibling);
		},

		render: function () {
			var block = document.createElement('div');
			block.className = 'local-equipment-deal-count'
				+ (this.options.highlight ? ' local-equipment-deal-count--highlight' : '');
			block.setAttribute(BLOCK_ATTR, String(this.options.dealId));

			var title = document.createElement('div');
			title.className = 'local-equipment-deal-count__title';
			title.textContent = BX.message('LOCAL_EQUIPMENT_DEAL_COUNT_TITLE');

			var value = document.createElement('div');
			value.className = 'local-equipment-deal-count__value';
			value.textContent = String(this.options.count);

			block.appendChild(title);
			block.appendChild(value);

			return block;
		}
	};
})();
