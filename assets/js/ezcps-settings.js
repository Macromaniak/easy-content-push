(function ($) {
	'use strict';

	function bindOriginRows() {
		$(document).on('click', '.ezcps-add-origin', function (e) {
			e.preventDefault();
			var $list = $(this).closest('.ezcps-origin-list');
			var index = $list.find('.ezcps-origin-row').length;
			var namePrefix = ezcpsSettings.optionKey + '[allowed_origins]';
			var row =
				'<div class="ezcps-origin-row">' +
				'<input type="url" class="regular-text code" name="' +
				namePrefix +
				'[' +
				index +
				']" value="" placeholder="' +
				ezcpsSettings.originPlaceholder +
				'" />' +
				'<button type="button" class="button ezcps-remove-origin">' +
				ezcpsSettings.removeOriginLabel +
				'</button></div>';
			$list.find('.ezcps-origin-rows').append(row);
		});

		$(document).on('click', '.ezcps-remove-origin', function (e) {
			e.preventDefault();
			var $rows = $(this).closest('.ezcps-origin-rows').find('.ezcps-origin-row');
			if ($rows.length <= 1) {
				$(this).closest('.ezcps-origin-row').find('input').val('');
				return;
			}
			$(this).closest('.ezcps-origin-row').remove();
		});
	}

	function bindToggleKey() {
		$(document).on('click', '.ezcps-toggle-key', function (e) {
			e.preventDefault();
			var target = $(this).data('target');
			var $input = $(target);
			if (!$input.length) {
				return;
			}
			var isPassword = $input.attr('type') === 'password';
			$input.attr('type', isPassword ? 'text' : 'password');
			$(this).text(isPassword ? ezcpsSettings.hideKeyLabel : ezcpsSettings.showKeyLabel);
		});
	}

	function bindCopyKey() {
		$(document).on('click', '.ezcps-copy-accept-key', function (e) {
			e.preventDefault();
			var $input = $('#ezcps_accept_key');
			if (!$input.length || !$input.val()) {
				window.alert(ezcpsSettings.copyKeyEmpty);
				return;
			}
			$input.attr('type', 'text');
			$input.trigger('select');
			try {
				document.execCommand('copy');
			} catch (err) {
				window.navigator.clipboard.writeText($input.val());
			}
			$input.attr('type', 'password');
		});
	}

	function bindGenerateKey() {
		$(document).on('click', '.ezcps-generate-accept-key', function (e) {
			e.preventDefault();
			var $acceptInput = $('#ezcps_accept_key');
			if ($acceptInput.val() && !window.confirm(ezcpsSettings.regenerateConfirm)) {
				return;
			}
			var $btn = $(this);
			$btn.prop('disabled', true);
			$.post(
				ezcpsSettings.ajaxUrl,
				{
					action: 'ezcps_generate_accept_key',
					security: ezcpsSettings.nonce,
				},
				function (response) {
					$btn.prop('disabled', false);
					if (response.success && response.data && response.data.key) {
						$('#ezcps_accept_key').val(response.data.key);
						$('.ezcps-accept-key-status')
							.text(ezcpsSettings.keyGenerated)
							.removeClass('is-error')
							.addClass('is-success');
					} else {
						var msg =
							response.data && response.data.message
								? response.data.message
								: ezcpsSettings.genericError;
						$('.ezcps-accept-key-status')
							.text(msg)
							.removeClass('is-success')
							.addClass('is-error');
					}
				}
			).fail(function () {
				$btn.prop('disabled', false);
				$('.ezcps-accept-key-status')
					.text(ezcpsSettings.genericError)
					.removeClass('is-success')
					.addClass('is-error');
			});
		});
	}

	function bindTestConnection() {
		$(document).on('click', '.ezcps-test-connection', function (e) {
			e.preventDefault();
			var $result = $('.ezcps-test-result');
			$result.text(ezcpsSettings.testing).removeClass('is-success is-error');
			$.post(
				ezcpsSettings.ajaxUrl,
				{
					action: 'ezcps_test_connection',
					security: ezcpsSettings.nonce,
				},
				function (response) {
					if (response.success) {
						$result
							.text(response.data.message)
							.addClass('is-success')
							.removeClass('is-error');
					} else {
						var msg =
							response.data && response.data.message
								? response.data.message
								: ezcpsSettings.genericError;
						$result.text(msg).addClass('is-error').removeClass('is-success');
					}
				}
			).fail(function () {
				$result
					.text(ezcpsSettings.genericError)
					.addClass('is-error')
					.removeClass('is-success');
			});
		});
	}

	$(function () {
		bindOriginRows();
		bindToggleKey();
		bindCopyKey();
		bindGenerateKey();
		bindTestConnection();
	});
})(jQuery);
