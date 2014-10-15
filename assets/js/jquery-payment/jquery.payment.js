// Generated by CoffeeScript 1.7.1
(function() {
	var $, cardFromNumber, cardFromType, cards, defaultFormat, formatBackCardNumber, formatBackExpiry, formatCardNumber, formatExpiry, formatForwardExpiry, formatForwardSlashAndSpace, hasTextSelected, luhnCheck, reFormatCardNumber, reFormatExpiry, restrictCVC, restrictCardNumber, restrictExpiry, restrictNumeric, setCardType,
		__slice = [].slice,
		__indexOf = [].indexOf || function(item) { for (var i = 0, l = this.length; i < l; i++) { if (i in this && this[i] === item) return i; } return -1; };

	$ = jQuery;

	$.payment = {};

	$.payment.fn = {};

	$.fn.payment = function() {
		var args, method;
		method = arguments[0], args = 2 <= arguments.length ? __slice.call(arguments, 1) : [];
		return $.payment.fn[method].apply(this, args);
	};

	defaultFormat = /(\d{1,4})/g;

	cards = [
		{
			type: 'visaelectron',
			pattern: /^4(026|17500|405|508|844|91[37])/,
			format: defaultFormat,
			length: [16],
			cvcLength: [3],
			luhn: true
		}, {
			type: 'maestro',
			pattern: /^(5(018|0[23]|[68])|6(39|7))/,
			format: defaultFormat,
			length: [12, 13, 14, 15, 16, 17, 18, 19],
			cvcLength: [3],
			luhn: true
		}, {
			type: 'forbrugsforeningen',
			pattern: /^600/,
			format: defaultFormat,
			length: [16],
			cvcLength: [3],
			luhn: true
		}, {
			type: 'dankort',
			pattern: /^5019/,
			format: defaultFormat,
			length: [16],
			cvcLength: [3],
			luhn: true
		}, {
			type: 'visa',
			pattern: /^4/,
			format: defaultFormat,
			length: [13, 16],
			cvcLength: [3],
			luhn: true
		}, {
			type: 'mastercard',
			pattern: /^5[0-5]/,
			format: defaultFormat,
			length: [16],
			cvcLength: [3],
			luhn: true
		}, {
			type: 'amex',
			pattern: /^3[47]/,
			format: /(\d{1,4})(\d{1,6})?(\d{1,5})?/,
			length: [15],
			cvcLength: [3, 4],
			luhn: true
		}, {
			type: 'dinersclub',
			pattern: /^3[0689]/,
			format: defaultFormat,
			length: [14],
			cvcLength: [3],
			luhn: true
		}, {
			type: 'discover',
			pattern: /^6([045]|22)/,
			format: defaultFormat,
			length: [16],
			cvcLength: [3],
			luhn: true
		}, {
			type: 'unionpay',
			pattern: /^(62|88)/,
			format: defaultFormat,
			length: [16, 17, 18, 19],
			cvcLength: [3],
			luhn: false
		}, {
			type: 'jcb',
			pattern: /^35/,
			format: defaultFormat,
			length: [16],
			cvcLength: [3],
			luhn: true
		}
	];

	cardFromNumber = function(num) {
		var card, _i, _len;
		num = (num + '').replace(/\D/g, '');
		for (_i = 0, _len = cards.length; _i < _len; _i++) {
			card = cards[_i];
			if (card.pattern.test(num)) {
				return card;
			}
		}
	};

	cardFromType = function(type) {
		var card, _i, _len;
		for (_i = 0, _len = cards.length; _i < _len; _i++) {
			card = cards[_i];
			if (card.type === type) {
				return card;
			}
		}
	};

	luhnCheck = function(num) {
		var digit, digits, odd, sum, _i, _len;
		odd = true;
		sum = 0;
		digits = (num + '').split('').reverse();
		for (_i = 0, _len = digits.length; _i < _len; _i++) {
			digit = digits[_i];
			digit = parseInt(digit, 10);
			if ((odd = !odd)) {
				digit *= 2;
			}
			if (digit > 9) {
				digit -= 9;
			}
			sum += digit;
		}
		return sum % 10 === 0;
	};

	hasTextSelected = function($target) {
		var _ref;
		if (($target.prop('selectionStart') != null) && $target.prop('selectionStart') !== $target.prop('selectionEnd')) {
			return true;
		}
		if (typeof document !== "undefined" && document !== null ? (_ref = document.selection) != null ? typeof _ref.createRange === "function" ? _ref.createRange().text : void 0 : void 0 : void 0) {
			return true;
		}
		return false;
	};

	reFormatCardNumber = function(e) {
		return setTimeout(function() {
			var $target, value;
			$target = $(e.currentTarget);
			value = $target.val();
			value = $.payment.formatCardNumber(value);
			return $target.val(value);
		});
	};

	formatCardNumber = function(e) {
		var $target, card, digit, length, re, upperLength, value;
		digit = String.fromCharCode(e.which);
		if (!/^\d+$/.test(digit)) {
			return;
		}
		$target = $(e.currentTarget);
		value = $target.val();
		card = cardFromNumber(value + digit);
		length = (value.replace(/\D/g, '') + digit).length;
		upperLength = 16;
		if (card) {
			upperLength = card.length[card.length.length - 1];
		}
		if (length >= upperLength) {
			return;
		}
		if (($target.prop('selectionStart') != null) && $target.prop('selectionStart') !== value.length) {
			return;
		}
		if (card && card.type === 'amex') {
			re = /^(\d{4}|\d{4}\s\d{6})$/;
		} else {
			re = /(?:^|\s)(\d{4})$/;
		}
		if (re.test(value)) {
			e.preventDefault();
			return setTimeout(function() {
				return $target.val(value + ' ' + digit);
			});
		} else if (re.test(value + digit)) {
			e.preventDefault();
			return setTimeout(function() {
				return $target.val(value + digit + ' ');
			});
		}
	};

	formatBackCardNumber = function(e) {
		var $target, value;
		$target = $(e.currentTarget);
		value = $target.val();
		if (e.which !== 8) {
			return;
		}
		if (($target.prop('selectionStart') != null) && $target.prop('selectionStart') !== value.length) {
			return;
		}
		if (/\d\s$/.test(value)) {
			e.preventDefault();
			return setTimeout(function() {
				return $target.val(value.replace(/\d\s$/, ''));
			});
		} else if (/\s\d?$/.test(value)) {
			e.preventDefault();
			return setTimeout(function() {
				return $target.val(value.replace(/\s\d?$/, ''));
			});
		}
	};

	reFormatExpiry = function(e) {
		return setTimeout(function() {
			var $target, value;
			$target = $(e.currentTarget);
			value = $target.val();
			value = $.payment.formatExpiry(value);
			return $target.val(value);
		});
	};

	formatExpiry = function(e) {
		var $target, digit, val;
		digit = String.fromCharCode(e.which);
		if (!/^\d+$/.test(digit)) {
			return;
		}
		$target = $(e.currentTarget);
		val = $target.val() + digit;
		if (/^\d$/.test(val) && (val !== '0' && val !== '1')) {
			e.preventDefault();
			return setTimeout(function() {
				return $target.val("0" + val + " / ");
			});
		} else if (/^\d\d$/.test(val)) {
			e.preventDefault();
			return setTimeout(function() {
				return $target.val("" + val + " / ");
			});
		}
	};

	formatForwardExpiry = function(e) {
		var $target, digit, val;
		digit = String.fromCharCode(e.which);
		if (!/^\d+$/.test(digit)) {
			return;
		}
		$target = $(e.currentTarget);
		val = $target.val();
		if (/^\d\d$/.test(val)) {
			return $target.val("" + val + " / ");
		}
	};

	formatForwardSlashAndSpace = function(e) {
		var $target, val, which;
		which = String.fromCharCode(e.which);
		if (!(which === '/' || which === ' ')) {
			return;
		}
		$target = $(e.currentTarget);
		val = $target.val();
		if (/^\d$/.test(val) && val !== '0') {
			return $target.val("0" + val + " / ");
		}
	};

	formatBackExpiry = function(e) {
		var $target, value;
		$target = $(e.currentTarget);
		value = $target.val();
		if (e.which !== 8) {
			return;
		}
		if (($target.prop('selectionStart') != null) && $target.prop('selectionStart') !== value.length) {
			return;
		}
		if (/\s\/\s\d?$/.test(value)) {
			e.preventDefault();
			return setTimeout(function() {
				return $target.val(value.replace(/\s\/\s\d?$/, ''));
			});
		}
	};

	restrictNumeric = function(e) {
		var input;
		if (e.metaKey || e.ctrlKey) {
			return true;
		}
		if (e.which === 32) {
			return false;
		}
		if (e.which === 0) {
			return true;
		}
		if (e.which < 33) {
			return true;
		}
		input = String.fromCharCode(e.which);
		return !!/[\d\s]/.test(input);
	};

	restrictCardNumber = function(e) {
		var $target, card, digit, value;
		$target = $(e.currentTarget);
		digit = String.fromCharCode(e.which);
		if (!/^\d+$/.test(digit)) {
			return;
		}
		if (hasTextSelected($target)) {
			return;
		}
		value = ($target.val() + digit).replace(/\D/g, '');
		card = cardFromNumber(value);
		if (card) {
			return value.length <= card.length[card.length.length - 1];
		} else {
			return value.length <= 16;
		}
	};

	restrictExpiry = function(e) {
		var $target, digit, value;
		$target = $(e.currentTarget);
		digit = String.fromCharCode(e.which);
		if (!/^\d+$/.test(digit)) {
			return;
		}
		if (hasTextSelected($target)) {
			return;
		}
		value = $target.val() + digit;
		value = value.replace(/\D/g, '');
		if (value.length > 6) {
			return false;
		}
	};

	restrictCVC = function(e) {
		var $target, digit, val;
		$target = $(e.currentTarget);
		digit = String.fromCharCode(e.which);
		if (!/^\d+$/.test(digit)) {
			return;
		}
		if (hasTextSelected($target)) {
			return;
		}
		val = $target.val() + digit;
		return val.length <= 4;
	};

	setCardType = function(e) {
		var $target, allTypes, card, cardType, val;
		$target = $(e.currentTarget);
		val = $target.val();
		cardType = $.payment.cardType(val) || 'unknown';
		if (!$target.hasClass(cardType)) {
			allTypes = (function() {
				var _i, _len, _results;
				_results = [];
				for (_i = 0, _len = cards.length; _i < _len; _i++) {
					card = cards[_i];
					_results.push(card.type);
				}
				return _results;
			})();
			$target.removeClass('unknown');
			$target.removeClass(allTypes.join(' '));
			$target.addClass(cardType);
			$target.toggleClass('identified', cardType !== 'unknown');
			return $target.trigger('payment.cardType', cardType);
		}
	};

	$.payment.fn.formatCardCVC = function() {
		this.payment('restrictNumeric');
		this.on('keypress', restrictCVC);
		return this;
	};

	$.payment.fn.formatCardExpiry = function() {
		this.payment('restrictNumeric');
		this.on('keypress', restrictExpiry);
		this.on('keypress', formatExpiry);
		this.on('keypress', formatForwardSlashAndSpace);
		this.on('keypress', formatForwardExpiry);
		this.on('keydown', formatBackExpiry);
		this.on('change', reFormatExpiry);
		this.on('input', reFormatExpiry);
		return this;
	};

	$.payment.fn.formatCardNumber = function() {
		this.payment('restrictNumeric');
		this.on('keypress', restrictCardNumber);
		this.on('keypress', formatCardNumber);
		this.on('keydown', formatBackCardNumber);
		this.on('keyup', setCardType);
		this.on('paste', reFormatCardNumber);
		this.on('change', reFormatCardNumber);
		this.on('input', reFormatCardNumber);
		this.on('input', setCardType);
		return this;
	};

	$.payment.fn.restrictNumeric = function() {
		this.on('keypress', restrictNumeric);
		return this;
	};

	$.payment.fn.cardExpiryVal = function() {
		return $.payment.cardExpiryVal($(this).val());
	};

	$.payment.cardExpiryVal = function(value) {
		var month, prefix, year, _ref;
		value = value.replace(/\s/g, '');
		_ref = value.split('/', 2), month = _ref[0], year = _ref[1];
		if ((year != null ? year.length : void 0) === 2 && /^\d+$/.test(year)) {
			prefix = (new Date).getFullYear();
			prefix = prefix.toString().slice(0, 2);
			year = prefix + year;
		}
		month = parseInt(month, 10);
		year = parseInt(year, 10);
		return {
			month: month,
			year: year
		};
	};

	$.payment.validateCardNumber = function(num) {
		var card, _ref;
		num = (num + '').replace(/\s+|-/g, '');
		if (!/^\d+$/.test(num)) {
			return false;
		}
		card = cardFromNumber(num);
		if (!card) {
			return false;
		}
		return (_ref = num.length, __indexOf.call(card.length, _ref) >= 0) && (card.luhn === false || luhnCheck(num));
	};

	$.payment.validateCardExpiry = function(month, year) {
		var currentTime, expiry, _ref;
		if (typeof month === 'object' && 'month' in month) {
			_ref = month, month = _ref.month, year = _ref.year;
		}
		if (!(month && year)) {
			return false;
		}
		month = $.trim(month);
		year = $.trim(year);
		if (!/^\d+$/.test(month)) {
			return false;
		}
		if (!/^\d+$/.test(year)) {
			return false;
		}
		if (!((1 <= month && month <= 12))) {
			return false;
		}
		if (year.length === 2) {
			if (year < 70) {
				year = "20" + year;
			} else {
				year = "19" + year;
			}
		}
		if (year.length !== 4) {
			return false;
		}
		expiry = new Date(year, month);
		currentTime = new Date;
		expiry.setMonth(expiry.getMonth() - 1);
		expiry.setMonth(expiry.getMonth() + 1, 1);
		return expiry > currentTime;
	};

	$.payment.validateCardCVC = function(cvc, type) {
		var card, _ref;
		cvc = $.trim(cvc);
		if (!/^\d+$/.test(cvc)) {
			return false;
		}
		card = cardFromType(type);
		if (card != null) {
			return _ref = cvc.length, __indexOf.call(card.cvcLength, _ref) >= 0;
		} else {
			return cvc.length >= 3 && cvc.length <= 4;
		}
	};

	$.payment.cardType = function(num) {
		var _ref;
		if (!num) {
			return null;
		}
		return ((_ref = cardFromNumber(num)) != null ? _ref.type : void 0) || null;
	};

	$.payment.formatCardNumber = function(num) {
		var card, groups, upperLength, _ref;
		card = cardFromNumber(num);
		if (!card) {
			return num;
		}
		upperLength = card.length[card.length.length - 1];
		num = num.replace(/\D/g, '');
		num = num.slice(0, upperLength);
		if (card.format.global) {
			return (_ref = num.match(card.format)) != null ? _ref.join(' ') : void 0;
		} else {
			groups = card.format.exec(num);
			if (groups == null) {
				return;
			}
			groups.shift();
			groups = $.grep(groups, function(n) {
				return n;
			});
			return groups.join(' ');
		}
	};

	$.payment.formatExpiry = function(expiry) {
		var mon, parts, sep, year;
		parts = expiry.match(/^\D*(\d{1,2})(\D+)?(\d{1,4})?/);
		if (!parts) {
			return '';
		}
		mon = parts[1] || '';
		sep = parts[2] || '';
		year = parts[3] || '';
		if (year.length > 0 || (sep.length > 0 && !(/\ \/?\ ?/.test(sep)))) {
			sep = ' / ';
		}
		if (mon.length === 1 && (mon !== '0' && mon !== '1')) {
			mon = "0" + mon;
			sep = ' / ';
		}
		return mon + sep + year;
	};

}).call(this);
