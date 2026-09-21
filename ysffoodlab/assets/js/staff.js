/**
 * YSF Food Lab — garson, kasiyer ve mutfak ekranı.
 * Genel site betiğinden bağımsızdır, jQuery kullanmaz.
 */
( function () {
	'use strict';

	var settings = window.YSF || {};
	var strings = settings.i18n || {};
	var pollMs = Number( settings.pollMs ) || 4000;

	function qs( selector, scope ) {
		return ( scope || document ).querySelector( selector );
	}

	function qsa( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function t( key, fallback ) {
		return strings[ key ] || fallback || '';
	}

	function applyTwoFactorChallenge( form, payload ) {
		var box = qs( '[data-ysf-2fa]', form );
		var setup = qs( '[data-ysf-2fa-setup]', form );
		var codeField = qs( '[name="ysf_2fa_code"]', form );
		var ticket = qs( '[name="ysf_2fa_ticket"]', form );
		var qr = qs( '[data-ysf-2fa-qr]', form );
		var secret = qs( '[data-ysf-2fa-secret]', form );

		if ( box ) {
			box.hidden = false;
		}

		if ( ticket && payload.ticket ) {
			ticket.value = payload.ticket;
		}

		if ( '2fa_setup' === payload.step && setup ) {
			setup.hidden = false;

			if ( qr && payload.qr ) {
				qr.src = payload.qr;
				qr.hidden = false;
			}

			if ( secret ) {
				secret.textContent = payload.secret || '';
			}
		}

		if ( codeField ) {
			codeField.required = true;
			codeField.focus();
		}
	}

	function escapeHtml( value ) {
		return String( value == null ? '' : value )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function formatPrice( amount ) {
		var value = Number( amount ) || 0;
		var decimals = Math.abs( value - Math.round( value ) ) < 0.005 ? 0 : 2;
		var formatted;

		try {
			formatted = value.toLocaleString( 'tr-TR', {
				minimumFractionDigits: decimals,
				maximumFractionDigits: decimals
			} );
		} catch ( e ) {
			formatted = value.toFixed( decimals );
		}

		return formatted + ' ' + ( settings.currency || '₺' );
	}

	function request( action, data ) {
		var body = new FormData();

		body.append( 'action', action );
		body.append( 'nonce', settings.nonce || '' );

		Object.keys( data || {} ).forEach( function ( key ) {
			var value = data[ key ];

			if ( Array.isArray( value ) ) {
				value.forEach( function ( entry ) {
					body.append( key + '[]', entry );
				} );
				return;
			}

			body.append( key, value );
		} );

		return fetch( settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} ).then( function ( response ) {
			return response.json().then( function ( json ) {
				return { ok: response.ok && json && json.success, payload: json && json.data ? json.data : {} };
			} );
		} );
	}

	function collectForm( form ) {
		var data = {};

		qsa( 'input, textarea, select', form ).forEach( function ( field ) {
			if ( ! field.name || 'ysf_hp' === field.name ) {
				return;
			}

			if ( 'radio' === field.type && ! field.checked ) {
				return;
			}

			if ( 'checkbox' === field.type ) {
				if ( field.checked ) {
					data[ field.name ] = field.value || '1';
				}
				return;
			}

			data[ field.name ] = field.value;
		} );

		return data;
	}

	function showFlash( box, message, ok ) {
		if ( ! box ) {
			return;
		}

		box.hidden = false;

		if ( box.hasAttribute( 'data-ysf-result' ) ) {
			box.className = 'ysf-alert ysf-field--full ' + ( ok ? 'ysf-alert--ok' : 'ysf-alert--err' );
		} else {
			box.className = 'ysf-pos__flash ' + ( ok ? 'is-ok' : 'is-err' );
		}

		box.textContent = message;
	}

	function ageLabel( seconds ) {
		var mins = Math.floor( ( Number( seconds ) || 0 ) / 60 );

		if ( mins < 1 ) {
			return t( 'kds_just', 'Şimdi' );
		}

		return t( 'kds_ago', '%s dk' ).replace( '%s', String( mins ) );
	}

	function tableTitle( number ) {
		return t( 'pos_table', 'Masa %s' ).replace( '%s', String( number ) );
	}

	function registerWorker() {
		if ( ! ( 'serviceWorker' in navigator ) ) {
			return;
		}

		var url = window.location.pathname + window.location.search;
		var join = url.indexOf( '?' ) === -1 ? '?' : '&';

		navigator.serviceWorker.register( url + join + 'ysf_sw=1' ).catch( function () {} );
	}

	/* ------------------------------------------------------------------ *
	 * Giriş
	 * ------------------------------------------------------------------ */

	function initLogin() {
		var form = qs( '[data-ysf-form="login"]' );

		if ( ! form ) {
			return;
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var button = qs( '[data-ysf-submit]', form );
			var result = qs( '[data-ysf-result]', form );
			var label = button ? button.textContent : '';

			if ( ! form.checkValidity() ) {
				showFlash( result, t( 'form_required', 'Zorunlu alanları doldurun.' ), false );
				form.reportValidity();
				return;
			}

			if ( button ) {
				button.disabled = true;
				button.textContent = t( 'form_sending', 'Gönderiliyor…' );
			}

			request( 'ysf_login', collectForm( form ) ).then( function ( response ) {
				if ( response.ok && response.payload && ( '2fa' === response.payload.step || '2fa_setup' === response.payload.step ) ) {
					applyTwoFactorChallenge( form, response.payload );
					showFlash( result, response.payload.message || t( 'tfa_setup_prompt', 'Authenticator kodunu yazın.' ), true );

					if ( button ) {
						button.disabled = false;
						button.textContent = t( 'tfa_continue', 'Kodu doğrula' );
					}

					return;
				}

				if ( response.ok ) {
					var okMessage = response.payload.message || t( 'acc_login_ok', 'Giriş yapıldı…' );

					if ( response.payload.backups && response.payload.backups.length ) {
						okMessage += ' ' + t( 'tfa_backups_once', 'Yedek kodlar:' ) + ' ' + response.payload.backups.join( '  ' );
						showFlash( result, okMessage, true );
						window.setTimeout( function () {
							window.location.href = response.payload.redirect || window.location.href;
						}, 8000 );
						return;
					}

					showFlash( result, okMessage, true );
					window.location.href = response.payload.redirect || window.location.href;
					return;
				}

				showFlash( result, response.payload.message || t( 'form_error', 'Bir sorun oluştu.' ), false );

				if ( button ) {
					button.disabled = false;
					button.textContent = label;
				}
			} ).catch( function () {
				showFlash( result, t( 'form_error', 'Bir sorun oluştu.' ), false );

				if ( button ) {
					button.disabled = false;
					button.textContent = label;
				}
			} );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Garson
	 * ------------------------------------------------------------------ */

	function initWaiter() {
		var root = qs( '[data-ysf-waiter]' );

		if ( ! root ) {
			return;
		}

		var views = {
			floor: qs( '[data-ysf-view="floor"]', root ),
			table: qs( '[data-ysf-view="table"]', root ),
			move: qs( '[data-ysf-view="move"]', root ),
			menu: qs( '[data-ysf-view="menu"]', root )
		};
		var flash = qs( '[data-ysf-pos-flash]', root );
		var grid = qs( '[data-ysf-tables]', root );
		var moveGrid = qs( '[data-ysf-move-tables]', root );
		var ticketsBox = qs( '[data-ysf-tickets]', root );
		var itemsBox = qs( '[data-ysf-menu-items]', root );
		var catsBox = qs( '[data-ysf-menu-cats]', root );
		var search = qs( '[data-ysf-menu-search]', root );
		var sendBtn = qs( '[data-ysf-send]', root );
		var sendLabel = qs( '[data-ysf-send-label]', root );
		var noteInput = qs( '[data-ysf-ticket-note]', root );
		var state = {
			table: '',
			tables: [],
			menu: null,
			cart: {},
			cat: '',
			term: '',
			timer: null,
			sending: false,
			moving: false
		};

		function showView( name ) {
			Object.keys( views ).forEach( function ( key ) {
				var node = views[ key ];

				if ( ! node ) {
					return;
				}

				var on = key === name;

				node.hidden = ! on;
				node.classList.toggle( 'is-active', on );
			} );
		}

		function cartLines() {
			return Object.keys( state.cart ).map( function ( id ) {
				return state.cart[ id ];
			} ).filter( function ( line ) {
				return line && line.qty > 0;
			} );
		}

		function cartTotal() {
			return cartLines().reduce( function ( sum, line ) {
				return sum + ( line.price * line.qty );
			}, 0 );
		}

		function syncSend() {
			var lines = cartLines();
			var count = lines.reduce( function ( sum, line ) {
				return sum + line.qty;
			}, 0 );

			sendBtn.disabled = count < 1;
			sendLabel.textContent = count
				? t( 'pos_send', 'Mutfağa gönder' ) + ' · ' + count + ' · ' + formatPrice( cartTotal() )
				: t( 'pos_send', 'Mutfağa gönder' );
		}

		function renderTables( tables ) {
			if ( ! grid ) {
				return;
			}

			grid.innerHTML = '';

			( tables || [] ).forEach( function ( table ) {
				var occupied = 'empty' !== table.status;
				var button = document.createElement( 'button' );
				var amount = document.createElement( 'strong' );

				button.type = 'button';
				button.className = 'ysf-pos__table is-' + ( occupied ? 'occupied' : 'empty' );
				button.setAttribute( 'data-table', table.id );
				button.innerHTML = '<em>' + escapeHtml( tableTitle( table.id ) ) + '</em>';
				amount.textContent = occupied ? t( 'pos_busy', 'Dolu' ) : t( 'pos_empty', 'Boş' );
				button.appendChild( amount );

				if ( occupied && table.count ) {
					var meta = document.createElement( 'span' );

					meta.textContent = table.count + ' · ' + ( table.label || '' );
					button.appendChild( meta );
				}

				button.addEventListener( 'click', function () {
					openTable( table.id );
				} );

				grid.appendChild( button );
			} );
		}

		function loadTables() {
			return request( 'ysf_floor_tables', {} ).then( function ( response ) {
				if ( response.ok ) {
					state.tables = response.payload.tables || [];
					renderTables( state.tables );

					if ( views.move && views.move.classList.contains( 'is-active' ) ) {
						renderMoveTables( state.tables );
					}
				}
			} );
		}

		function kitchenLabel( stateName ) {
			if ( 'cooking' === stateName ) {
				return t( 'kds_cooking', 'Hazırlanıyor' );
			}

			if ( 'ready' === stateName ) {
				return t( 'kds_ready', 'Hazır' );
			}

			if ( 'served' === stateName ) {
				return t( 'kds_served', 'Servis' );
			}

			return t( 'kds_queued', 'Yeni' );
		}

		function renderTickets( payload ) {
			ticketsBox.innerHTML = '';
			qs( '[data-ysf-table-title]', root ).textContent = tableTitle( payload.table );
			qs( '[data-ysf-table-sum]', root ).textContent = payload.tickets && payload.tickets.length
				? payload.label
				: t( 'pos_no_tickets', 'Bu masada açık sipariş yok.' );

			var closeBtn = qs( '[data-ysf-close-table]', root );
			var moveBtn = qs( '[data-ysf-move-table]', root );
			var hasTickets = payload.tickets && payload.tickets.length;

			if ( closeBtn ) {
				closeBtn.disabled = ! hasTickets;
			}

			if ( moveBtn ) {
				moveBtn.disabled = ! hasTickets;
			}

			if ( ! payload.tickets || ! payload.tickets.length ) {
				return;
			}

			payload.tickets.forEach( function ( ticket ) {
				var card = document.createElement( 'article' );
				var head = document.createElement( 'header' );
				var list = document.createElement( 'ul' );

				card.className = 'ysf-pos__ticket is-' + ticket.kitchen;
				head.innerHTML = '<strong>#' + ticket.id + '</strong><span>' + escapeHtml( kitchenLabel( ticket.kitchen ) ) + '</span><em>' + escapeHtml( ageLabel( ticket.age ) ) + '</em>';
				card.appendChild( head );

				ticket.items.forEach( function ( line ) {
					var li = document.createElement( 'li' );

					li.innerHTML = '<b>' + escapeHtml( line.qty ) + '</b><span>' + escapeHtml( line.name ) + ( line.note ? ' <i>' + escapeHtml( line.note ) + '</i>' : '' ) + '</span>';
					list.appendChild( li );
				} );

				card.appendChild( list );

				if ( ticket.note ) {
					var note = document.createElement( 'p' );

					note.className = 'ysf-pos__ticket-note';
					note.textContent = ticket.note;
					card.appendChild( note );
				}

				if ( 'queued' === ticket.kitchen ) {
					var cancel = document.createElement( 'button' );

					cancel.type = 'button';
					cancel.className = 'ysf-link-btn ysf-link-btn--danger';
					cancel.textContent = t( 'pos_cancel', 'İptal' );
					cancel.addEventListener( 'click', function () {
						if ( ! window.confirm( t( 'pos_cancel_ask', 'Bu sipariş iptal edilsin mi?' ) ) ) {
							return;
						}

						request( 'ysf_floor_cancel', { id: ticket.id } ).then( function ( response ) {
							showFlash( flash, response.payload.message || t( 'form_error' ), response.ok );

							if ( response.ok ) {
								openTable( state.table );
							}
						} );
					} );
					card.appendChild( cancel );
				}

				ticketsBox.appendChild( card );
			} );
		}

		function openTable( id ) {
			state.table = String( id );
			showView( 'table' );

			request( 'ysf_floor_table', { table: state.table } ).then( function ( response ) {
				if ( response.ok ) {
					renderTickets( response.payload );
				} else {
					showFlash( flash, response.payload.message || t( 'form_error' ), false );
				}
			} );
		}

		function renderMenu() {
			if ( ! state.menu ) {
				return;
			}

			var term = ( state.term || '' ).toLocaleLowerCase( 'tr-TR' );

			itemsBox.innerHTML = '';

			state.menu.items.forEach( function ( item ) {
				if ( state.cat && String( item.cat ) !== String( state.cat ) ) {
					return;
				}

				var hay = ( item.name + ' ' + ( item.excerpt || '' ) ).toLocaleLowerCase( 'tr-TR' );

				if ( term && hay.indexOf( term ) === -1 ) {
					return;
				}

				var row = document.createElement( 'article' );
				var qty = state.cart[ item.id ] ? state.cart[ item.id ].qty : 0;

				row.className = 'ysf-pos__item' + ( item.sold ? ' is-sold' : '' ) + ( qty ? ' is-in' : '' );
				row.innerHTML = ( item.thumb ? '<img src="' + escapeHtml( item.thumb ) + '" alt="" width="56" height="56">' : '' ) +
					'<div><h3>' + escapeHtml( item.name ) + '</h3><p>' + escapeHtml( item.priceLabel ) + ( item.sold ? ' · ' + escapeHtml( t( 'sold_out', 'Tükendi' ) ) : '' ) + '</p></div>' +
					'<div class="ysf-pos__stepper">' +
					'<button type="button" data-act="minus" aria-label="-">−</button>' +
					'<span>' + qty + '</span>' +
					'<button type="button" data-act="plus" aria-label="+">+</button>' +
					'</div>';

				if ( item.sold ) {
					qsa( 'button', row ).forEach( function ( button ) {
						button.disabled = true;
					} );
				} else {
					qs( '[data-act="minus"]', row ).addEventListener( 'click', function () {
						changeQty( item, -1 );
					} );
					qs( '[data-act="plus"]', row ).addEventListener( 'click', function () {
						changeQty( item, 1 );
					} );
					row.addEventListener( 'click', function ( event ) {
						if ( event.target.closest( 'button' ) ) {
							return;
						}

						changeQty( item, 1 );
					} );
				}

				itemsBox.appendChild( row );
			} );

			syncSend();
		}

		function changeQty( item, delta ) {
			var current = state.cart[ item.id ] || { id: item.id, name: item.name, price: item.price, qty: 0 };
			var next = Math.max( 0, Math.min( 50, current.qty + delta ) );

			if ( next ) {
				current.qty = next;
				state.cart[ item.id ] = current;
			} else {
				delete state.cart[ item.id ];
			}

			renderMenu();
		}

		function renderCats() {
			catsBox.innerHTML = '';

			var all = document.createElement( 'button' );

			all.type = 'button';
			all.className = 'ysf-filter' + ( state.cat ? '' : ' is-active' );
			all.textContent = t( 'menu_all', 'Tümü' );
			all.addEventListener( 'click', function () {
				state.cat = '';
				renderCats();
				renderMenu();
			} );
			catsBox.appendChild( all );

			( state.menu.cats || [] ).forEach( function ( cat ) {
				var button = document.createElement( 'button' );

				button.type = 'button';
				button.className = 'ysf-filter' + ( String( state.cat ) === String( cat.id ) ? ' is-active' : '' );
				button.textContent = cat.name;
				button.addEventListener( 'click', function () {
					state.cat = cat.id;
					renderCats();
					renderMenu();
				} );
				catsBox.appendChild( button );
			} );
		}

		function openMenu() {
			showView( 'menu' );
			qs( '[data-ysf-menu-table]', root ).textContent = tableTitle( state.table );
			state.cart = {};
			if ( noteInput ) {
				noteInput.value = '';
			}
			syncSend();

			var ready = state.menu ? Promise.resolve() : request( 'ysf_floor_menu', {} ).then( function ( response ) {
				if ( response.ok ) {
					state.menu = response.payload;
				}
			} );

			ready.then( function () {
				if ( ! state.menu ) {
					return;
				}

				renderCats();
				renderMenu();
			} );
		}

		function goFloor() {
			state.table = '';
			state.cart = {};
			state.moving = false;
			if ( noteInput ) {
				noteInput.value = '';
			}
			showView( 'floor' );
			loadTables();
		}

		function openMove() {
			if ( ! state.table ) {
				return;
			}

			showView( 'move' );

			var fromLabel = qs( '[data-ysf-move-from]', root );

			if ( fromLabel ) {
				fromLabel.textContent = tableTitle( state.table );
			}

			loadTables();
		}

		function renderMoveTables( tables ) {
			if ( ! moveGrid ) {
				return;
			}

			moveGrid.innerHTML = '';

			( tables || state.tables || [] ).forEach( function ( table ) {
				var occupied = 'empty' !== table.status;
				var current = String( table.id ) === String( state.table );
				var button = document.createElement( 'button' );
				var amount = document.createElement( 'strong' );

				button.type = 'button';
				button.className = 'ysf-pos__table is-' + ( occupied ? 'occupied' : 'empty' ) + ( current ? ' is-current' : '' );
				button.setAttribute( 'data-table', table.id );
				button.disabled = current || state.moving;
				button.innerHTML = '<em>' + escapeHtml( tableTitle( table.id ) ) + '</em>';
				amount.textContent = current
					? t( 'pos_current', 'Bu masa' )
					: ( occupied ? t( 'pos_busy', 'Dolu' ) : t( 'pos_empty', 'Boş' ) );
				button.appendChild( amount );

				if ( occupied && table.count && ! current ) {
					var meta = document.createElement( 'span' );

					meta.textContent = table.count + ' · ' + ( table.label || '' );
					button.appendChild( meta );
				}

				if ( ! current ) {
					button.addEventListener( 'click', function () {
						moveTo( table );
					} );
				}

				moveGrid.appendChild( button );
			} );
		}

		function moveTo( table ) {
			if ( ! state.table || ! table || state.moving ) {
				return;
			}

			var occupied = 'empty' !== table.status;
			var ask = occupied
				? t( 'pos_move_merge', 'Masa %s dolu. Açık siparişler bu masayla birleştirilsin mi?' )
				: t( 'pos_move_ask', 'Açık siparişler Masa %s konumuna taşınsın mı?' );

			if ( ! window.confirm( ask.replace( '%s', String( table.id ) ) ) ) {
				return;
			}

			state.moving = true;
			renderMoveTables( state.tables );

			request( 'ysf_floor_move', { from: state.table, to: table.id } ).then( function ( response ) {
				state.moving = false;
				showFlash( flash, response.payload.message || t( 'form_error' ), response.ok );

				if ( response.ok ) {
					openTable( response.payload.table || table.id );
				} else {
					renderMoveTables( state.tables );
				}
			} ).catch( function () {
				state.moving = false;
				renderMoveTables( state.tables );
			} );
		}

		function sendOrder( thenFloor ) {
			var lines = cartLines();

			if ( ! lines.length ) {
				if ( thenFloor ) {
					goFloor();
				}
				return;
			}

			if ( state.sending ) {
				return;
			}

			state.sending = true;
			sendBtn.disabled = true;

			request( 'ysf_floor_submit', {
				table: state.table,
				note: noteInput ? noteInput.value : '',
				items: JSON.stringify( lines.map( function ( line ) {
					return { id: line.id, qty: line.qty };
				} ) )
			} ).then( function ( response ) {
				state.sending = false;
				showFlash( flash, response.payload.message || t( 'form_error' ), response.ok );

				if ( response.ok ) {
					state.cart = {};
					if ( noteInput ) {
						noteInput.value = '';
					}
					if ( thenFloor ) {
						goFloor();
					} else {
						openTable( state.table );
					}
				} else {
					syncSend();
				}
			} ).catch( function () {
				state.sending = false;
				syncSend();
			} );
		}

		function finishTable() {
			if ( views.menu && views.menu.classList.contains( 'is-active' ) && cartLines().length ) {
				sendOrder( true );
				return;
			}

			goFloor();
		}

		qsa( '[data-ysf-back-floor]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', goFloor );
		} );

		qsa( '[data-ysf-back-table]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				state.cart = {};
				if ( noteInput ) {
					noteInput.value = '';
				}
				openTable( state.table );
			} );
		} );

		qsa( '[data-ysf-done]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', finishTable );
		} );

		qs( '[data-ysf-open-menu]', root ).addEventListener( 'click', openMenu );
		sendBtn.addEventListener( 'click', function () {
			sendOrder( false );
		} );

		qs( '[data-ysf-close-table]', root ).addEventListener( 'click', function () {
			if ( ! state.table || ! window.confirm( t( 'pos_close_ask', 'Masa kapatılsın mı?' ) ) ) {
				return;
			}

			request( 'ysf_floor_close', { table: state.table } ).then( function ( response ) {
				showFlash( flash, response.payload.message || t( 'form_error' ), response.ok );

				if ( response.ok ) {
					state.table = '';
					showView( 'floor' );
					loadTables();
				}
			} );
		} );

		var moveBtn = qs( '[data-ysf-move-table]', root );

		if ( moveBtn ) {
			moveBtn.addEventListener( 'click', openMove );
		}

		if ( search ) {
			search.addEventListener( 'input', function () {
				state.term = search.value;
				renderMenu();
			} );
		}

		function poll() {
			if ( document.hidden ) {
				return;
			}

			if ( views.floor && views.floor.classList.contains( 'is-active' ) ) {
				loadTables();
			} else if ( views.move && views.move.classList.contains( 'is-active' ) ) {
				loadTables();
			} else if ( state.table && views.table && views.table.classList.contains( 'is-active' ) ) {
				request( 'ysf_floor_table', { table: state.table } ).then( function ( response ) {
					if ( response.ok ) {
						renderTickets( response.payload );
					}
				} );
			}
		}

		loadTables();
		state.timer = window.setInterval( poll, pollMs );
		document.addEventListener( 'visibilitychange', function () {
			if ( ! document.hidden ) {
				poll();
			}
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Kasiyer
	 * ------------------------------------------------------------------ */

	function initCashier() {
		var root = qs( '[data-ysf-cashier]' );

		if ( ! root ) {
			return;
		}

		var views = {
			floor: qs( '[data-ysf-view="floor"]', root ),
			table: qs( '[data-ysf-view="table"]', root )
		};
		var flash = qs( '[data-ysf-pos-flash]', root );
		var grid = qs( '[data-ysf-tables]', root );
		var ticketsBox = qs( '[data-ysf-tickets]', root );
		var state = {
			table: '',
			timer: null,
			paying: false
		};

		function showView( name ) {
			Object.keys( views ).forEach( function ( key ) {
				var node = views[ key ];

				if ( ! node ) {
					return;
				}

				var on = key === name;

				node.hidden = ! on;
				node.classList.toggle( 'is-active', on );
			} );
		}

		function kitchenLabel( stateName ) {
			if ( 'cooking' === stateName ) {
				return t( 'kds_cooking', 'Hazırlanıyor' );
			}

			if ( 'ready' === stateName ) {
				return t( 'kds_ready', 'Hazır' );
			}

			if ( 'served' === stateName ) {
				return t( 'kds_served', 'Servis' );
			}

			return t( 'kds_queued', 'Yeni' );
		}

		function renderSummary( payload ) {
			var summary = payload && payload.summary ? payload.summary : {};
			var openEl = qs( '[data-ysf-cash-open]', root );
			var openTotal = qs( '[data-ysf-cash-open-total]', root );
			var cashEl = qs( '[data-ysf-cash-cash]', root );
			var cardEl = qs( '[data-ysf-cash-card]', root );
			var todayEl = qs( '[data-ysf-cash-today]', root );

			if ( openEl ) {
				openEl.textContent = String( payload.openCount || 0 );
			}

			if ( openTotal ) {
				openTotal.textContent = payload.openLabel || formatPrice( 0 );
			}

			if ( cashEl ) {
				cashEl.textContent = summary.cashLabel || formatPrice( 0 );
			}

			if ( cardEl ) {
				cardEl.textContent = summary.cardLabel || formatPrice( 0 );
			}

			if ( todayEl ) {
				todayEl.textContent = summary.takenLabel || formatPrice( 0 );
			}
		}

		function statusLabel( status, table ) {
			if ( 'empty' === status ) {
				return t( 'pos_empty', 'Boş' );
			}

			if ( 'bill' === status || 'ready' === status ) {
				return t( 'pos_bill', 'Hesap' );
			}

			return table.label || t( 'pos_busy', 'Dolu' );
		}

		function renderTables( tables ) {
			if ( ! grid ) {
				return;
			}

			grid.innerHTML = '';

			( tables || [] ).forEach( function ( table ) {
				var occupied = 'empty' !== table.status;
				var button = document.createElement( 'button' );
				var amount = document.createElement( 'strong' );

				button.type = 'button';
				button.className = 'ysf-pos__table is-' + ( table.status || 'empty' );
				button.setAttribute( 'data-table', table.id );
				button.innerHTML = '<em>' + escapeHtml( tableTitle( table.id ) ) + '</em>';
				amount.textContent = occupied ? ( table.label || statusLabel( table.status, table ) ) : t( 'pos_empty', 'Boş' );
				button.appendChild( amount );

				if ( occupied && table.count ) {
					var meta = document.createElement( 'span' );

					meta.textContent = table.count + ' · ' + statusLabel( table.status, table );
					button.appendChild( meta );
				}

				button.addEventListener( 'click', function () {
					openTable( table.id );
				} );

				grid.appendChild( button );
			} );
		}

		function loadTables() {
			return request( 'ysf_cashier_tables', {} ).then( function ( response ) {
				if ( response.ok ) {
					renderSummary( response.payload );
					renderTables( response.payload.tables );
				}
			} );
		}

		function renderTickets( payload ) {
			ticketsBox.innerHTML = '';
			qs( '[data-ysf-table-title]', root ).textContent = tableTitle( payload.table );
			qs( '[data-ysf-table-sum]', root ).textContent = payload.tickets && payload.tickets.length
				? payload.label
				: t( 'pos_no_tickets', 'Bu masada açık sipariş yok.' );

			qsa( '[data-ysf-pay]', root ).forEach( function ( button ) {
				button.disabled = ! payload.tickets || ! payload.tickets.length || state.paying;
			} );

			if ( ! payload.tickets || ! payload.tickets.length ) {
				return;
			}

			payload.tickets.forEach( function ( ticket ) {
				var card = document.createElement( 'article' );
				var head = document.createElement( 'header' );
				var list = document.createElement( 'ul' );
				var total = document.createElement( 'p' );

				card.className = 'ysf-pos__ticket is-' + ticket.kitchen;
				head.innerHTML = '<strong>#' + ticket.id + '</strong><span>' + escapeHtml( kitchenLabel( ticket.kitchen ) ) + '</span><em>' + escapeHtml( ageLabel( ticket.age ) ) + '</em>';
				card.appendChild( head );

				ticket.items.forEach( function ( line ) {
					var item = document.createElement( 'li' );
					var lineTotal = ( Number( line.price ) || 0 ) * ( Number( line.qty ) || 1 );

					item.textContent = line.qty + ' × ' + line.name + ' · ' + formatPrice( lineTotal );

					if ( line.note ) {
						item.appendChild( document.createTextNode( ' — ' + line.note ) );
					}

					list.appendChild( item );
				} );

				card.appendChild( list );

				if ( ticket.note ) {
					var note = document.createElement( 'p' );

					note.className = 'ysf-pos__ticket-note';
					note.textContent = ticket.note;
					card.appendChild( note );
				}

				if ( ticket.waiter ) {
					var waiter = document.createElement( 'p' );

					waiter.className = 'ysf-muted';
					waiter.textContent = ticket.waiter;
					card.appendChild( waiter );
				}

				total.className = 'ysf-cash__ticket-total';
				total.textContent = ticket.totalLabel || formatPrice( ticket.total );
				card.appendChild( total );
				ticketsBox.appendChild( card );
			} );
		}

		function openTable( number ) {
			state.table = String( number );
			showView( 'table' );

			return request( 'ysf_cashier_table', { table: state.table } ).then( function ( response ) {
				if ( response.ok ) {
					renderTickets( response.payload );
				} else {
					showFlash( flash, response.payload.message || t( 'form_error' ), false );
				}
			} );
		}

		function goFloor() {
			state.table = '';
			showView( 'floor' );
			loadTables();
		}

		function pay( method ) {
			if ( ! state.table || state.paying ) {
				return;
			}

			var label = 'card' === method ? t( 'cash_pay_card', 'Kart' ) : t( 'cash_pay_cash', 'Nakit' );
			var ask = t( 'cash_pay_ask', '%s tahsil edilsin mi? Masa kapanacak.' ).replace( '%s', label );

			if ( ! window.confirm( ask ) ) {
				return;
			}

			state.paying = true;
			qsa( '[data-ysf-pay]', root ).forEach( function ( button ) {
				button.disabled = true;
			} );

			request( 'ysf_cashier_pay', { table: state.table, method: method } ).then( function ( response ) {
				showFlash( flash, response.payload.message || t( 'form_error' ), response.ok );
				state.paying = false;

				if ( response.ok ) {
					state.table = '';
					showView( 'floor' );
					loadTables();
				} else {
					qsa( '[data-ysf-pay]', root ).forEach( function ( button ) {
						button.disabled = false;
					} );
				}
			} );
		}

		qsa( '[data-ysf-back-floor]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', goFloor );
		} );

		qsa( '[data-ysf-pay]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				pay( button.getAttribute( 'data-ysf-pay' ) );
			} );
		} );

		function poll() {
			if ( document.hidden ) {
				return;
			}

			if ( views.floor && views.floor.classList.contains( 'is-active' ) ) {
				loadTables();
			} else if ( state.table && views.table && views.table.classList.contains( 'is-active' ) ) {
				request( 'ysf_cashier_table', { table: state.table } ).then( function ( response ) {
					if ( response.ok ) {
						renderTickets( response.payload );
					}
				} );
			}
		}

		loadTables();
		state.timer = window.setInterval( poll, pollMs );
		document.addEventListener( 'visibilitychange', function () {
			if ( ! document.hidden ) {
				poll();
			}
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Mutfak ekranı
	 * ------------------------------------------------------------------ */

	function beep() {
		try {
			var ctx = new ( window.AudioContext || window.webkitAudioContext )();
			var osc = ctx.createOscillator();
			var gain = ctx.createGain();

			osc.type = 'square';
			osc.frequency.value = 880;
			gain.gain.value = 0.05;
			osc.connect( gain );
			gain.connect( ctx.destination );
			osc.start();
			window.setTimeout( function () {
				osc.stop();
				ctx.close();
			}, 180 );
		} catch ( e ) {}
	}

	function initKds() {
		var root = qs( '[data-ysf-kds]' );

		if ( ! root ) {
			return;
		}

		var soundOn = false;
		var known = {};
		var soundBtn = qs( '[data-ysf-kds-sound]', root );
		var empty = qs( '[data-ysf-kds-empty]', root );
		var clock = qs( '[data-ysf-kds-clock]', root );
		var list = qs( '[data-ysf-kds-list]', root );
		var countBadge = qs( '[data-ysf-kds-count]', root );

		function tickClock() {
			if ( ! clock ) {
				return;
			}

			var now = new Date();
			var label = now.toLocaleTimeString( 'tr-TR', { hour: '2-digit', minute: '2-digit' } );

			clock.textContent = label;
			clock.setAttribute( 'datetime', now.toISOString() );
		}

		function nextState( current ) {
			if ( 'queued' === current ) {
				return 'cooking';
			}

			if ( 'cooking' === current ) {
				return 'ready';
			}

			return 'served';
		}

		function nextLabel( current ) {
			if ( 'queued' === current ) {
				return t( 'kds_take', 'Aldım' );
			}

			if ( 'cooking' === current ) {
				return t( 'kds_done', 'Hazır' );
			}

			return t( 'kds_serve', 'Servis edildi' );
		}

		function channelLabel( ticket ) {
			var label;

			if ( 'table' === ticket.channel && ticket.table ) {
				return tableTitle( ticket.table );
			}

			if ( 'delivery' === ticket.channel ) {
				label = t( 'order_delivery', 'Adrese teslim' );
			} else if ( 'pickup' === ticket.channel ) {
				label = t( 'order_pickup', 'Gel al' );
			} else {
				label = t( 'kds_online', 'Online' );
			}

			return label + ' · WhatsApp';
		}

		function kitchenLabel( stateName ) {
			if ( 'cooking' === stateName ) {
				return t( 'kds_cooking', 'Hazırlanıyor' );
			}

			if ( 'ready' === stateName ) {
				return t( 'kds_ready', 'Hazır' );
			}

			return t( 'kds_queued', 'Yeni' );
		}

		function render( tickets ) {
			var incoming = {};
			var visible = 0;

			if ( list ) {
				list.innerHTML = '';
			}

			( tickets || [] ).forEach( function ( ticket, index ) {
				incoming[ ticket.id ] = true;

				if ( ! list || 'served' === ticket.kitchen ) {
					return;
				}

				visible += 1;

				var card = document.createElement( 'article' );
				var head = document.createElement( 'header' );
				var items = document.createElement( 'ul' );
				var action = document.createElement( 'button' );
				var closeBtn = null;
				var isNew = ! known[ ticket.id ] && 'queued' === ticket.kitchen;

				card.className = 'ysf-kds__ticket is-' + ticket.kitchen + ( isNew ? ' is-new' : '' ) + ( 'table' === ticket.channel ? '' : ' is-online' );
				head.innerHTML = '<b>#' + ( index + 1 ) + '</b><strong>' + escapeHtml( channelLabel( ticket ) ) + '</strong><span>' + escapeHtml( kitchenLabel( ticket.kitchen ) ) + '</span><em>' + escapeHtml( ageLabel( ticket.age ) ) + '</em>';
				card.appendChild( head );

				ticket.items.forEach( function ( line ) {
					var li = document.createElement( 'li' );

					li.innerHTML = '<b>' + escapeHtml( line.qty ) + '</b><span>' + escapeHtml( line.name ) + ( line.note ? ' <i>' + escapeHtml( line.note ) + '</i>' : '' ) + '</span>';
					items.appendChild( li );
				} );

				card.appendChild( items );

				if ( 'table' !== ticket.channel ) {
					var who = document.createElement( 'p' );
					var bits = [];

					who.className = 'ysf-kds__who';

					if ( ticket.name ) {
						bits.push( ticket.name );
					}

					if ( ticket.phone ) {
						bits.push( ticket.phone );
					}

					if ( ticket.address ) {
						bits.push( ticket.address );
					}

					if ( ticket.time ) {
						bits.push( ticket.time );
					}

					if ( ticket.totalLabel ) {
						bits.push( ticket.totalLabel );
					}

					if ( bits.length ) {
						who.textContent = bits.join( ' · ' );
						card.appendChild( who );
					}
				}

				if ( ticket.note ) {
					var note = document.createElement( 'p' );

					note.textContent = ticket.note;
					card.appendChild( note );
				}

				if ( 'table' !== ticket.channel && 'queued' === ticket.kitchen ) {
					var hint = document.createElement( 'p' );

					hint.className = 'ysf-kds__hint';
					hint.textContent = t( 'kds_wa_check', 'WhatsApp’tan kontrol edin. Mesaj iletilmediyse veya iptal edildiyse hazırlamadan kapatın.' );
					card.appendChild( hint );
				}

				action.type = 'button';
				action.className = 'ysf-btn';
				action.textContent = nextLabel( ticket.kitchen );
				action.addEventListener( 'click', function () {
					if ( 'table' !== ticket.channel && 'queued' === ticket.kitchen ) {
						if ( ! window.confirm( t( 'kds_wa_take_ask', 'WhatsApp’ta bu siparişin mesajı iletildi mi? İletilmediyse hazırlamadan kapatın.' ) ) ) {
							return;
						}
					}

					action.disabled = true;

					if ( closeBtn ) {
						closeBtn.disabled = true;
					}

					request( 'ysf_kds_state', { id: ticket.id, state: nextState( ticket.kitchen ) } ).then( function () {
						load();
					} );
				} );

				var actions = document.createElement( 'div' );

				actions.className = 'ysf-kds__actions';
				actions.appendChild( action );

				if ( 'table' !== ticket.channel && 'queued' === ticket.kitchen ) {
					closeBtn = document.createElement( 'button' );
					closeBtn.type = 'button';
					closeBtn.className = 'ysf-btn ysf-btn--ghost';
					closeBtn.textContent = t( 'kds_close', 'Kapat' );
					closeBtn.addEventListener( 'click', function () {
						if ( ! window.confirm( t( 'kds_close_ask', 'WhatsApp’ta mesaj yok veya iptal edildi. Sipariş hazırlanmadan kapatılsın mı?' ) ) ) {
							return;
						}

						action.disabled = true;
						closeBtn.disabled = true;
						request( 'ysf_kds_close', { id: ticket.id } ).then( function () {
							load();
						} );
					} );
					actions.appendChild( closeBtn );
				}

				card.appendChild( actions );
				list.appendChild( card );
			} );

			if ( countBadge ) {
				countBadge.textContent = String( visible );
			}

			if ( empty ) {
				empty.hidden = visible > 0;
			}

			var fresh = false;

			Object.keys( incoming ).forEach( function ( id ) {
				if ( ! known[ id ] ) {
					fresh = true;
				}
			} );

			if ( Object.keys( known ).length && fresh && soundOn ) {
				beep();
			}

			known = incoming;
		}

		function load() {
			return request( 'ysf_kds_tickets', {} ).then( function ( response ) {
				if ( response.ok ) {
					render( response.payload.tickets );
				}
			} );
		}

		if ( soundBtn ) {
			soundBtn.addEventListener( 'click', function () {
				soundOn = ! soundOn;
				soundBtn.setAttribute( 'aria-pressed', soundOn ? 'true' : 'false' );
				soundBtn.textContent = soundOn ? t( 'kds_sound_on', 'Ses açık' ) : t( 'kds_sound_off', 'Ses kapalı' );

				if ( soundOn ) {
					beep();
				}
			} );
		}

		tickClock();
		window.setInterval( tickClock, 15000 );
		load();
		window.setInterval( function () {
			if ( ! document.hidden ) {
				load();
			}
		}, pollMs );
		document.addEventListener( 'visibilitychange', function () {
			if ( ! document.hidden ) {
				load();
			}
		} );
	}

	function boot() {
		registerWorker();
		initLogin();
		initWaiter();
		initCashier();
		initKds();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
