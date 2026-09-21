/**
 * YSF Food Lab teması — arayüz betiği.
 * jQuery kullanılmaz, tüm modüller birbirinden bağımsız çalışır.
 */
( function () {
	'use strict';

	var CART_KEY = 'ysf_cart_v1';
	var settings = window.YSF || {};
	var strings = settings.i18n || {};

	/* ------------------------------------------------------------------ *
	 * Yardımcılar
	 * ------------------------------------------------------------------ */

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

	function openWhatsApp( url ) {
		if ( ! url ) {
			return;
		}

		var link = document.createElement( 'a' );

		link.href = url;
		link.target = '_blank';
		link.rel = 'noopener noreferrer';
		document.body.appendChild( link );
		link.click();
		document.body.removeChild( link );
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
			return response.text().then( function ( text ) {
				var json = null;

				try {
					json = text ? JSON.parse( text ) : null;
				} catch ( e ) {
					json = null;
				}

				if ( ! json || 'object' !== typeof json ) {
					return {
						ok: false,
						payload: { message: t( 'form_error', 'Bir sorun oluştu. Lütfen tekrar deneyin veya bizi arayın.' ) }
					};
				}

				return {
					ok: response.ok && json.success,
					payload: json.data && 'object' === typeof json.data ? json.data : {}
				};
			} );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Mobil menü
	 * ------------------------------------------------------------------ */

	function initNav() {
		var burger = qs( '[data-ysf-burger]' );
		var nav = qs( '#ysf-nav' );
		var scrim = qs( '[data-ysf-scrim]' );

		if ( ! burger || ! nav ) {
			return;
		}

		function setOpen( open ) {
			nav.classList.toggle( 'is-open', open );
			burger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			document.body.classList.toggle( 'ysf-no-scroll', open );

			if ( scrim ) {
				scrim.classList.toggle( 'is-visible', open );
			}
		}

		burger.addEventListener( 'click', function () {
			setOpen( burger.getAttribute( 'aria-expanded' ) !== 'true' );
		} );

		if ( scrim ) {
			scrim.addEventListener( 'click', function () {
				setOpen( false );
				closeCart();
			} );
		}

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				setOpen( false );
				closeCart();
			}
		} );

		qsa( 'a', nav ).forEach( function ( link ) {
			link.addEventListener( 'click', function () {
				setOpen( false );
			} );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Yapışkan başlık gölgesi
	 * ------------------------------------------------------------------ */

	function initHeader() {
		var header = qs( '[data-ysf-header]' );

		if ( ! header ) {
			return;
		}

		var onScroll = function () {
			header.classList.toggle( 'is-stuck', window.scrollY > 8 );
		};

		onScroll();
		window.addEventListener( 'scroll', onScroll, { passive: true } );
	}

	/* ------------------------------------------------------------------ *
	 * Duyuru şeridi
	 * ------------------------------------------------------------------ */

	function initTicker() {
		var ticker = qs( '[data-ysf-ticker]' );

		if ( ! ticker ) {
			return;
		}

		var items = qsa( '.ysf-topbar__item', ticker );

		if ( items.length < 2 ) {
			return;
		}

		var index = 0;

		setInterval( function () {
			items[ index ].classList.remove( 'is-active' );
			index = ( index + 1 ) % items.length;
			items[ index ].classList.add( 'is-active' );
		}, 5000 );
	}

	/* ------------------------------------------------------------------ *
	 * Açık / kapalı durumu
	 *
	 * Sayfa tam sayfa önbellekten gelse bile durum güncel kalsın diye
	 * hesap tarayıcıda, restoranın saat dilimine göre yeniden yapılır.
	 * ------------------------------------------------------------------ */

	var DAY_KEYS = [ 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' ];

	function siteNow() {
		var now = new Date();
		var utcMs = now.getTime() + ( now.getTimezoneOffset() * 60000 );

		return new Date( utcMs + ( ( Number( settings.utcOffset ) || 0 ) * 1000 ) );
	}

	function rangesFor( dayKey ) {
		var raw = ( settings.hours || {} )[ dayKey ] || '';

		if ( ! raw || /kapal|closed/i.test( raw ) ) {
			return [];
		}

		return raw.split( ',' ).map( function ( part ) {
			var match = part.match( /(\d{1,2})[:.](\d{2})\s*[-–]\s*(\d{1,2})[:.](\d{2})/ );

			if ( ! match ) {
				return null;
			}

			return {
				start: ( Number( match[ 1 ] ) * 60 ) + Number( match[ 2 ] ),
				end: ( Number( match[ 3 ] ) * 60 ) + Number( match[ 4 ] )
			};
		} ).filter( Boolean );
	}

	function isOpenNow() {
		var now = siteNow();
		var minutes = ( now.getHours() * 60 ) + now.getMinutes();
		var todayIndex = now.getDay();
		var open = false;

		rangesFor( DAY_KEYS[ todayIndex ] ).forEach( function ( range ) {
			if ( range.end <= range.start ) {
				// Gece yarısını aşan aralık, bugün başlıyor.
				if ( minutes >= range.start ) {
					open = true;
				}
				return;
			}

			if ( minutes >= range.start && minutes < range.end ) {
				open = true;
			}
		} );

		// Dünden devam eden, gece yarısını aşan aralık.
		rangesFor( DAY_KEYS[ ( todayIndex + 6 ) % 7 ] ).forEach( function ( range ) {
			if ( range.end <= range.start && minutes < range.end ) {
				open = true;
			}
		} );

		return open;
	}

	function initStatus() {
		var badge = qs( '[data-ysf-status]' );

		if ( ! badge || ! settings.hours ) {
			return;
		}

		function update() {
			var open = isOpenNow();
			var label = qs( '[data-ysf-status-label]', badge );

			badge.classList.toggle( 'ysf-status--closed', ! open );

			if ( label ) {
				label.textContent = open ? t( 'open_now', 'Şu an açık' ) : t( 'closed_now', 'Şu an kapalı' );
			}
		}

		update();
		setInterval( update, 60000 );
	}

	/* ------------------------------------------------------------------ *
	 * Sepet
	 * ------------------------------------------------------------------ */

	function readCart() {
		try {
			var raw = window.localStorage.getItem( CART_KEY );
			var parsed = raw ? JSON.parse( raw ) : [];

			return Array.isArray( parsed ) ? parsed.filter( function ( line ) {
				return line && line.id && line.qty > 0;
			} ) : [];
		} catch ( e ) {
			return [];
		}
	}

	function writeCart( cart ) {
		try {
			window.localStorage.setItem( CART_KEY, JSON.stringify( cart ) );
		} catch ( e ) {
			// Depolama kapalıysa sessizce devam et.
		}

		renderCart();
		renderOrderSummary();
	}

	function cartCount() {
		return readCart().reduce( function ( sum, line ) {
			return sum + Number( line.qty );
		}, 0 );
	}

	function cartSubtotal() {
		return readCart().reduce( function ( sum, line ) {
			return sum + ( Number( line.price ) * Number( line.qty ) );
		}, 0 );
	}

	function deliveryFee( subtotal, type ) {
		if ( 'delivery' !== type ) {
			return 0;
		}

		var fee = Number( settings.deliveryFee ) || 0;
		var freeOver = Number( settings.freeOver ) || 0;

		if ( freeOver > 0 && subtotal >= freeOver ) {
			return 0;
		}

		return fee;
	}

	function addToCart( item ) {
		var cart = readCart();
		var found = false;

		cart = cart.map( function ( line ) {
			if ( Number( line.id ) === Number( item.id ) ) {
				found = true;
				line.qty = Number( line.qty ) + 1;
			}

			return line;
		} );

		if ( ! found ) {
			cart.push( { id: Number( item.id ), name: item.name, price: Number( item.price ), qty: 1 } );
		}

		writeCart( cart );
	}

	function setQty( id, qty ) {
		var cart = readCart().map( function ( line ) {
			if ( Number( line.id ) === Number( id ) ) {
				line.qty = Math.max( 0, Math.min( 50, qty ) );
			}

			return line;
		} ).filter( function ( line ) {
			return line.qty > 0;
		} );

		writeCart( cart );
	}

	function openCart() {
		var panel = qs( '[data-ysf-cart]' );
		var scrim = qs( '[data-ysf-scrim]' );

		if ( ! panel ) {
			return;
		}

		panel.classList.add( 'is-open' );
		panel.setAttribute( 'aria-hidden', 'false' );

		if ( scrim ) {
			scrim.classList.add( 'is-visible' );
		}
	}

	function closeCart() {
		var panel = qs( '[data-ysf-cart]' );
		var scrim = qs( '[data-ysf-scrim]' );

		if ( ! panel ) {
			return;
		}

		panel.classList.remove( 'is-open' );
		panel.setAttribute( 'aria-hidden', 'true' );

		if ( scrim ) {
			scrim.classList.remove( 'is-visible' );
		}
	}

	function qtyControl( line ) {
		var wrap = document.createElement( 'div' );
		wrap.className = 'ysf-qty';

		var minus = document.createElement( 'button' );
		minus.type = 'button';
		minus.textContent = '−';
		minus.setAttribute( 'aria-label', '-' );
		minus.addEventListener( 'click', function () {
			setQty( line.id, Number( line.qty ) - 1 );
		} );

		var output = document.createElement( 'output' );
		output.textContent = String( line.qty );

		var plus = document.createElement( 'button' );
		plus.type = 'button';
		plus.textContent = '+';
		plus.setAttribute( 'aria-label', '+' );
		plus.addEventListener( 'click', function () {
			setQty( line.id, Number( line.qty ) + 1 );
		} );

		wrap.appendChild( minus );
		wrap.appendChild( output );
		wrap.appendChild( plus );

		return wrap;
	}

	function buildLines( container ) {
		var cart = readCart();

		container.textContent = '';

		if ( ! cart.length ) {
			var empty = document.createElement( 'p' );
			empty.className = 'ysf-card__text';
			empty.textContent = t( 'cart_empty', 'Sepetiniz boş.' );
			container.appendChild( empty );
			return;
		}

		cart.forEach( function ( line ) {
			var row = document.createElement( 'div' );
			row.className = 'ysf-cart-line';

			var name = document.createElement( 'span' );
			name.className = 'ysf-cart-line__name';
			name.textContent = line.name;

			var price = document.createElement( 'span' );
			price.className = 'ysf-price';
			price.style.fontSize = '1rem';
			price.textContent = formatPrice( Number( line.price ) * Number( line.qty ) );

			var note = document.createElement( 'span' );
			note.className = 'ysf-cart-line__note';
			note.appendChild( qtyControl( line ) );

			row.appendChild( name );
			row.appendChild( price );
			row.appendChild( note );

			container.appendChild( row );
		} );
	}

	function buildTotals( container, type ) {
		var subtotal = cartSubtotal();
		var fee = deliveryFee( subtotal, type || 'delivery' );

		container.textContent = '';

		if ( ! readCart().length ) {
			return;
		}

		function row( label, value, isGrand ) {
			var line = document.createElement( 'div' );

			if ( isGrand ) {
				line.className = 'ysf-totals__grand';
			}

			var left = document.createElement( 'span' );
			left.textContent = label;

			var right = document.createElement( 'span' );
			right.textContent = value;

			line.appendChild( left );
			line.appendChild( right );
			container.appendChild( line );
		}

		row( t( 'subtotal', 'Ara toplam' ), formatPrice( subtotal ) );

		if ( 'delivery' === ( type || 'delivery' ) && ( Number( settings.deliveryFee ) > 0 || fee > 0 ) ) {
			row( t( 'delivery_fee', 'Teslimat' ), fee > 0 ? formatPrice( fee ) : t( 'free', 'Ücretsiz' ) );
		}

		row( t( 'total', 'Toplam' ), formatPrice( subtotal + fee ), true );

		var min = Number( settings.minOrder ) || 0;

		if ( min > 0 && subtotal < min ) {
			var warn = document.createElement( 'div' );
			warn.style.color = '#a02c2c';
			warn.style.display = 'block';
			warn.textContent = t( 'min_order_warning', 'Minimum sipariş: %s' ).replace( '%s', formatPrice( min ) );
			container.appendChild( warn );
		}
	}

	function renderCart() {
		var count = cartCount();
		var button = qs( '[data-ysf-cart-open]' );
		var badge = qs( '[data-ysf-cart-count]' );
		var body = qs( '[data-ysf-cart-body]' );
		var totals = qs( '[data-ysf-cart-totals]' );

		if ( button ) {
			button.hidden = 0 === count;
		}

		if ( badge ) {
			badge.textContent = String( count );
		}

		if ( body ) {
			buildLines( body );
		}

		if ( totals ) {
			buildTotals( totals, currentOrderType() );
		}

		var checkout = qs( '[data-ysf-cart-checkout]' );

		if ( checkout ) {
			checkout.setAttribute( 'aria-disabled', 0 === count ? 'true' : 'false' );
		}
	}

	function initCart() {
		qsa( '.ysf-add' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				addToCart( {
					id: button.getAttribute( 'data-id' ),
					name: button.getAttribute( 'data-name' ),
					price: button.getAttribute( 'data-price' )
				} );

				var original = button.textContent;
				button.textContent = t( 'added', 'Eklendi' );
				button.disabled = true;

				setTimeout( function () {
					button.textContent = original;
					button.disabled = false;
				}, 1200 );

				openCart();
			} );
		} );

		var openBtn = qs( '[data-ysf-cart-open]' );
		var closeBtn = qs( '[data-ysf-cart-close]' );
		var clearBtn = qs( '[data-ysf-cart-clear]' );

		if ( openBtn ) {
			openBtn.addEventListener( 'click', openCart );
		}

		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', closeCart );
		}

		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', function () {
				writeCart( [] );
				closeCart();
			} );
		}

		renderCart();
		syncPrices();
	}

	/**
	 * Sepetteki fiyatları sunucudaki güncel değerlerle eşitler.
	 */
	function syncPrices() {
		var cart = readCart();

		if ( ! cart.length || ! settings.ajaxUrl ) {
			return;
		}

		request( 'ysf_get_prices', { ids: cart.map( function ( line ) {
			return line.id;
		} ) } ).then( function ( result ) {
			if ( ! result.ok ) {
				return;
			}

			var changed = false;

			var next = cart.filter( function ( line ) {
				var fresh = result.payload[ line.id ];

				if ( ! fresh || ! fresh.orderable ) {
					changed = true;
					return false;
				}

				if ( Number( fresh.price ) !== Number( line.price ) || fresh.name !== line.name ) {
					line.price = Number( fresh.price );
					line.name = fresh.name;
					changed = true;
				}

				return true;
			} );

			if ( changed ) {
				writeCart( next );
			}
		} ).catch( function () {} );
	}

	/* ------------------------------------------------------------------ *
	 * Sipariş sayfası
	 * ------------------------------------------------------------------ */

	function currentOrderType() {
		var checked = qs( '[data-ysf-form="order"] input[name="type"]:checked' );

		return checked ? checked.value : 'delivery';
	}

	function renderOrderSummary() {
		var lines = qs( '[data-ysf-order-lines]' );
		var totals = qs( '[data-ysf-order-totals]' );

		if ( lines ) {
			buildLines( lines );
		}

		if ( totals ) {
			buildTotals( totals, currentOrderType() );
		}
	}

	function toggleOrderFields() {
		var type = currentOrderType();

		qsa( '[data-ysf-when]' ).forEach( function ( field ) {
			var forType = field.getAttribute( 'data-ysf-when' );
			var visible = forType === type;

			field.hidden = ! visible;

			qsa( 'input, textarea, select', field ).forEach( function ( input ) {
				if ( 'address' === input.name ) {
					input.required = visible;
				}
			} );
		} );

		renderOrderSummary();
	}

	function initOrderForm() {
		var form = qs( '[data-ysf-form="order"]' );

		if ( ! form ) {
			renderOrderSummary();
			return;
		}

		qsa( '[data-ysf-to-delivery]' ).forEach( function ( link ) {
			link.addEventListener( 'click', function ( event ) {
				var target = qs( '#ysf-teslimat' );

				if ( ! target ) {
					return;
				}

				event.preventDefault();
				target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			} );
		} );

		qsa( 'input[name="type"]', form ).forEach( function ( input ) {
			input.addEventListener( 'change', toggleOrderFields );
		} );

		toggleOrderFields();

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var cart = readCart();
			var result = qs( '[data-ysf-result]', form );

			if ( ! cart.length ) {
				showResult( result, t( 'order_empty_error', 'Sepetiniz boş.' ), false );
				return;
			}

			if ( ! form.checkValidity() ) {
				showResult( result, t( 'form_required', 'Zorunlu alanları doldurun.' ), false );
				form.reportValidity();
				return;
			}

			var data = collectForm( form );
			data.items = JSON.stringify( cart.map( function ( line ) {
				return { id: line.id, qty: line.qty };
			} ) );

			submitForm( form, 'ysf_submit_order', data, function ( payload ) {
				writeCart( [] );

				if ( payload.whatsapp ) {
					openWhatsApp( payload.whatsapp );
					appendWhatsappButton( qs( '[data-ysf-result]', form ), payload.whatsapp );
				}
			} );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Formlar (rezervasyon, iletişim, sipariş)
	 * ------------------------------------------------------------------ */

	function collectForm( form ) {
		var data = {};
		var extras = [];

		qsa( 'input, textarea, select', form ).forEach( function ( field ) {
			if ( ! field.name || 'ysf_hp' === field.name ) {
				return;
			}

			if ( 'extras[]' === field.name ) {
				if ( field.checked ) {
					extras.push( field.value );
				}
				return;
			}

			if ( 'file' === field.type ) {
				if ( field.files && field.files[ 0 ] ) {
					data[ field.name ] = field.files[ 0 ];
				}
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

		if ( extras.length ) {
			data.extras = extras;
		}

		return data;
	}

	function showResult( box, message, ok ) {
		if ( ! box ) {
			return;
		}

		box.hidden = false;
		box.className = 'ysf-alert ' + ( ok ? 'ysf-alert--ok' : 'ysf-alert--err' );
		box.textContent = message;
		box.setAttribute( 'role', ok ? 'status' : 'alert' );
		box.scrollIntoView( { behavior: 'smooth', block: 'center' } );
	}

	function appendWhatsappButton( box, url ) {
		if ( ! box ) {
			return;
		}

		var hint = document.createElement( 'p' );
		var link = document.createElement( 'a' );

		hint.className = 'ysf-alert__hint';
		hint.textContent = t( 'order_wa_check', 'Mesaj iletilmezse veya iptal edilirse siparişi hazırlamayız. WhatsApp’tan gönderildiğini kontrol edin; gitmediyse aşağıdaki butondan tekrar gönderin.' );

		link.className = 'ysf-btn ysf-btn--wa ysf-btn--sm';
		link.href = url;
		link.target = '_blank';
		link.rel = 'noopener noreferrer';
		link.textContent = t( 'order_send_wa', 'WhatsApp’tan gönder' );

		box.appendChild( hint );
		box.appendChild( link );
	}

	function submitForm( form, action, data, onSuccess, options ) {
		var button = qs( '[data-ysf-submit]', form );
		var result = qs( '[data-ysf-result]', form );
		var label = button ? button.textContent : '';
		var keepValues = !! ( options && options.keepValues );

		if ( button ) {
			button.disabled = true;
			button.textContent = t( 'form_sending', 'Gönderiliyor…' );
		}

		request( action, data ).then( function ( response ) {
			if ( response.ok ) {
				showResult( result, response.payload.message || t( 'form_submit', 'Gönderildi' ), true );

				if ( ! keepValues ) {
					form.reset();
				}

				if ( 'function' === typeof onSuccess ) {
					onSuccess( response.payload );
				}
			} else {
				showResult( result, response.payload.message || t( 'form_error', 'Bir sorun oluştu.' ), false );
			}
		} ).catch( function () {
			showResult( result, t( 'form_error', 'Bir sorun oluştu.' ), false );
		} ).then( function () {
			if ( button ) {
				button.disabled = false;
				button.textContent = label;
			}
		} );
	}

	function initSimpleForms() {
		var map = {
			reservation: 'ysf_submit_reservation',
			contact: 'ysf_submit_contact'
		};

		Object.keys( map ).forEach( function ( name ) {
			var form = qs( '[data-ysf-form="' + name + '"]' );

			if ( ! form ) {
				return;
			}

			form.addEventListener( 'submit', function ( event ) {
				event.preventDefault();

				var result = qs( '[data-ysf-result]', form );

				if ( ! form.checkValidity() ) {
					showResult( result, t( 'form_required', 'Zorunlu alanları doldurun.' ), false );
					form.reportValidity();
					return;
				}

				submitForm( form, map[ name ], collectForm( form ) );
			} );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Üyelik: giriş, kayıt, profil ve adres defteri
	 * ------------------------------------------------------------------ */

	var geoCache = null;

	/**
	 * İl/ilçe listesini bir kez indirir.
	 */
	function loadGeo() {
		if ( geoCache ) {
			return Promise.resolve( geoCache );
		}

		if ( ! settings.geoUrl ) {
			return Promise.resolve( {} );
		}

		return fetch( settings.geoUrl, { credentials: 'same-origin' } ).then( function ( response ) {
			return response.json();
		} ).then( function ( json ) {
			geoCache = json || {};

			return geoCache;
		} ).catch( function () {
			return {};
		} );
	}

	/**
	 * İl seçimine göre ilçe listesini kurar, zorunlu alanları işaretler.
	 */
	function initAddressFields() {
		var grids = qsa( '[data-ysf-addr]' );

		if ( ! grids.length ) {
			return;
		}

		var requiredNames = [ 'addr_il', 'addr_ilce', 'addr_mahalle', 'addr_sokak', 'addr_bina' ];

		grids.forEach( function ( grid ) {
			var province = qs( '[data-ysf-province]', grid );
			var district = qs( '[data-ysf-district]', grid );

			if ( ! province || ! district ) {
				return;
			}

			function fillDistricts( selected ) {
				loadGeo().then( function ( geo ) {
					var list = geo[ province.value ] || [];
					var placeholder = document.createElement( 'option' );

					district.innerHTML = '';
					placeholder.value = '';
					placeholder.textContent = province.value
						? t( 'addr_select', 'Seçiniz' )
						: t( 'addr_select_first', 'Önce il seçin' );
					district.appendChild( placeholder );

					list.forEach( function ( name ) {
						var option = document.createElement( 'option' );

						option.value = name;
						option.textContent = name;
						option.selected = ( name === selected );
						district.appendChild( option );
					} );
				} );
			}

			// Adres kısmen doldurulduysa zorunlu alanlar devreye girer; tamamen
			// boş bırakılan adres geçerlidir.
			function syncRequired() {
				var form = grid.closest( 'form' );
				var fields = qsa( 'input, select, textarea', grid );
				var skip = form && 'register' === form.getAttribute( 'data-ysf-form' );
				var filled = ! skip && fields.some( function ( field ) {
					return '' !== String( field.value ).trim();
				} );

				fields.forEach( function ( field ) {
					if ( -1 !== requiredNames.indexOf( field.name ) ) {
						field.required = filled;
					}
				} );
			}

			province.addEventListener( 'change', function () {
				fillDistricts( '' );
				syncRequired();
			} );

			grid.addEventListener( 'input', syncRequired );
			grid.addEventListener( 'change', syncRequired );

			if ( province.value ) {
				fillDistricts( district.value );
			}

			syncRequired();
		} );
	}

	function clearPasswordFields( form ) {
		qsa( '.ysf-pass input, input[type="password"]', form ).forEach( function ( field ) {
			field.value = '';
		} );
	}

	function initPasswordToggles() {
		qsa( 'input[type="password"]' ).forEach( function ( field ) {
			if ( field.closest( '.ysf-pass' ) || field.closest( '.ysf-hp' ) ) {
				return;
			}

			var wrap = document.createElement( 'div' );
			var button = document.createElement( 'button' );
			var label = document.createElement( 'span' );

			wrap.className = 'ysf-pass';
			field.parentNode.insertBefore( wrap, field );
			wrap.appendChild( field );

			button.type = 'button';
			button.className = 'ysf-pass__toggle';
			button.setAttribute( 'aria-pressed', 'false' );
			button.setAttribute( 'aria-label', t( 'acc_pass_show', 'Göster' ) );
			button.innerHTML = '<svg class="ysf-icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c-5 0-9 4.5-10 7 1 2.5 5 7 10 7s9-4.5 10-7c-1-2.5-5-7-10-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/></svg>';
			label.textContent = t( 'acc_pass_show', 'Göster' );
			button.appendChild( label );
			wrap.appendChild( button );

			button.addEventListener( 'click', function () {
				var show = 'password' === field.type;

				field.type = show ? 'text' : 'password';
				button.setAttribute( 'aria-pressed', show ? 'true' : 'false' );
				button.setAttribute( 'aria-label', show ? t( 'acc_pass_hide', 'Gizle' ) : t( 'acc_pass_show', 'Göster' ) );
				label.textContent = show ? t( 'acc_pass_hide', 'Gizle' ) : t( 'acc_pass_show', 'Göster' );
			} );
		} );
	}

	function showRegisterVerify( payload ) {
		var registerPanel = qs( '[data-ysf-register-panel]' );
		var verifyPanel = qs( '[data-ysf-verify-panel]' );
		var tokenField = qs( '#ysf-verify-token' );
		var destEl = qs( '[data-ysf-verify-dest]' );
		var codeField = qs( '#ysf-verify-code' );
		var verifyForm = qs( '[data-ysf-form="verify"]' );
		var verifyResult = verifyForm ? qs( '[data-ysf-result]', verifyForm ) : null;

		if ( registerPanel ) {
			registerPanel.hidden = true;
		}

		if ( verifyPanel ) {
			verifyPanel.hidden = false;
		}

		if ( tokenField ) {
			tokenField.value = payload && payload.token ? payload.token : '';
		}

		if ( destEl ) {
			var parts = [];

			if ( payload && payload.phone ) {
				parts.push( payload.phone );
			}

			if ( payload && payload.email ) {
				parts.push( payload.email );
			}

			destEl.textContent = parts.join( ' · ' );
		}

		if ( codeField ) {
			codeField.value = payload && payload.code ? payload.code : '';
			codeField.focus();
		}

		if ( verifyResult && payload && payload.message ) {
			showResult( verifyResult, payload.message, true );
		}
	}

	function showRegisterForm() {
		var registerPanel = qs( '[data-ysf-register-panel]' );
		var verifyPanel = qs( '[data-ysf-verify-panel]' );

		if ( registerPanel ) {
			registerPanel.hidden = false;
		}

		if ( verifyPanel ) {
			verifyPanel.hidden = true;
		}
	}

	function validateRegisterForm( form ) {
		prepareRegisterForm( form );

		qsa( '[data-ysf-addr] input, [data-ysf-addr] select, [data-ysf-addr] textarea', form ).forEach( function ( field ) {
			field.required = false;
			if ( field.setCustomValidity ) {
				field.setCustomValidity( '' );
			}
		} );

		var name = qs( '[name="name"]', form );
		var email = qs( '[name="email"]', form );
		var phone = qs( '[name="phone"]', form );
		var pass = qs( '[name="password"]', form );
		var pass2 = qs( '[name="password2"]', form );
		var consent = qs( '[name="consent"]', form );
		var nameVal = name ? String( name.value || '' ).trim() : '';
		var emailVal = email ? String( email.value || '' ).trim() : '';
		var phoneVal = phone ? String( phone.value || '' ).trim() : '';
		var passVal = pass ? String( pass.value || '' ) : '';
		var pass2Val = pass2 ? String( pass2.value || '' ) : '';

		if ( name ) {
			name.value = nameVal;
		}

		if ( ! nameVal ) {
			return { field: name, message: t( 'form_name', 'Ad Soyad' ) + ': ' + t( 'form_required', 'Lütfen zorunlu alanları doldurun.' ) };
		}

		if ( ! emailVal || -1 === emailVal.indexOf( '@' ) || -1 === emailVal.indexOf( '.' ) ) {
			return { field: email, message: t( 'acc_email_invalid', 'E-posta adresi geçersiz görünüyor.' ) };
		}

		if ( phoneVal.replace( /\D+/g, '' ).length < 10 ) {
			return { field: phone, message: t( 'form_phone_invalid', 'Geçerli bir telefon numarası yazın.' ) };
		}

		if ( passVal.length < 8 ) {
			return { field: pass, message: t( 'acc_pass_short', 'Şifre en az 8 karakter olmalı.' ) };
		}

		if ( passVal !== pass2Val ) {
			return { field: pass2, message: t( 'acc_pass_mismatch', 'Şifreler birbiriyle aynı değil.' ) };
		}

		if ( consent && ! consent.checked ) {
			return { field: consent, message: t( 'acc_need_consent', 'Kayıt için alttaki onay kutusunu işaretleyin.' ) };
		}

		return null;
	}

	function invalidFormMessage( form ) {
		var field = form.querySelector( ':invalid' );

		if ( ! field ) {
			return t( 'form_required', 'Lütfen zorunlu alanları doldurun.' );
		}

		if ( field.validity.valueMissing ) {
			if ( 'consent' === field.name ) {
				return t( 'form_consent', 'Kişisel verilerimin bu talep kapsamında işlenmesine onay veriyorum.' );
			}

			return t( 'form_required', 'Lütfen zorunlu alanları doldurun.' );
		}

		if ( 'email' === field.name || field.validity.typeMismatch ) {
			return t( 'acc_email_invalid', 'E-posta adresi geçersiz görünüyor.' );
		}

		if ( 'username' === field.name ) {
			return t( 'acc_username_invalid', 'Kullanıcı adı harfle başlamalı ve yalnızca küçük harf, rakam, nokta veya alt çizgi içerebilir.' );
		}

		return field.validationMessage || t( 'form_required', 'Lütfen zorunlu alanları doldurun.' );
	}

	function prepareRegisterForm( form ) {
		var user = qs( '[name="username"]', form );
		var email = qs( '[name="email"]', form );
		var zip = qs( '[name="addr_posta_kodu"]', form );
		var userVal = user ? String( user.value || '' ).trim() : '';
		var emailVal = email ? String( email.value || '' ).trim() : '';

		if ( email ) {
			email.value = emailVal;
		}

		if ( user && -1 !== userVal.indexOf( '@' ) ) {
			if ( email && ! emailVal ) {
				email.value = userVal;
				emailVal = userVal;
			}

			userVal = userVal.split( '@' )[ 0 ];
		}

		if ( ! userVal && emailVal && -1 !== emailVal.indexOf( '@' ) ) {
			userVal = emailVal.split( '@' )[ 0 ];
		}

		if ( user ) {
			user.value = userVal.toLowerCase().replace( /[^a-z0-9._-]/g, '' ).slice( 0, 30 );
		}

		if ( zip && zip.value && ! /^\d{5}$/.test( String( zip.value ).trim() ) ) {
			zip.value = '';
		}
	}

	function initAccountForms() {
		var map = {
			login: 'ysf_login',
			register: 'ysf_register',
			lostpass: 'ysf_lost_password',
			verify: 'ysf_verify_register',
			resetpass: 'ysf_reset_password',
			profile: 'ysf_save_profile'
		};

		Object.keys( map ).forEach( function ( name ) {
			var form = qs( '[data-ysf-form="' + name + '"]' );

			if ( ! form ) {
				return;
			}

			if ( 'register' === name ) {
				var email = qs( '[name="email"]', form );
				var user = qs( '[name="username"]', form );

				if ( email && user ) {
					email.addEventListener( 'blur', function () {
						if ( ! String( user.value || '' ).trim() && -1 !== String( email.value || '' ).indexOf( '@' ) ) {
							user.value = String( email.value ).split( '@' )[ 0 ].toLowerCase().replace( /[^a-z0-9._-]/g, '' ).slice( 0, 30 );
						}
					} );
				}
			}

			form.addEventListener( 'submit', function ( event ) {
				event.preventDefault();

				var result = qs( '[data-ysf-result]', form );

				if ( 'register' === name ) {
					var invalid = validateRegisterForm( form );

					if ( invalid ) {
						showResult( result, invalid.message, false );

						if ( invalid.field && invalid.field.focus ) {
							invalid.field.focus();
						}

						return;
					}
				} else if ( ! form.checkValidity() ) {
					showResult( result, invalidFormMessage( form ), false );
					form.reportValidity();
					return;
				}

				submitForm( form, map[ name ], collectForm( form ), function ( payload ) {
					if ( 'profile' === name ) {
						clearPasswordFields( form );
					}

					if ( 'register' === name && payload && 'verify' === payload.step ) {
						showRegisterVerify( payload );
						return;
					}

					if ( 'login' === name && payload && ( '2fa' === payload.step || '2fa_setup' === payload.step ) ) {
						applyTwoFactorChallenge( form, payload );
						window.setTimeout( function () {
							var button = qs( '[data-ysf-submit]', form );

							if ( button ) {
								button.textContent = t( 'tfa_continue', 'Kodu doğrula' );
							}
						}, 0 );
						return;
					}

					if ( 'login' === name && payload && payload.backups && payload.backups.length ) {
						var resultBox = qs( '[data-ysf-result]', form );
						var backupText = ( payload.message || t( 'tfa_on', 'İki adımlı doğrulama açıldı.' ) ) + ' ' + t( 'tfa_backups_once', 'Yedek kodlar:' ) + ' ' + payload.backups.join( '  ' );
						showResult( resultBox, backupText, true );

						if ( payload.redirect ) {
							window.setTimeout( function () {
								window.location.href = payload.redirect;
							}, 8000 );
						}

						return;
					}

					if ( payload.redirect ) {
						window.setTimeout( function () {
							window.location.href = payload.redirect;
						}, 800 );
					}
				}, { keepValues: 'profile' === name || 'register' === name || 'verify' === name || 'resetpass' === name || 'login' === name } );
			} );
		} );

		var resend = qs( '[data-ysf-resend-verify]' );
		var back = qs( '[data-ysf-verify-back]' );
		var verifyForm = qs( '[data-ysf-form="verify"]' );

		if ( resend && verifyForm ) {
			resend.addEventListener( 'click', function () {
				var tokenField = qs( '#ysf-verify-token' );
				var result = qs( '[data-ysf-result]', verifyForm );
				var label = resend.textContent;

				if ( ! tokenField || ! tokenField.value ) {
					showRegisterForm();
					return;
				}

				resend.disabled = true;
				resend.textContent = t( 'form_sending', 'Gönderiliyor…' );

				request( 'ysf_resend_verify', { token: tokenField.value } ).then( function ( response ) {
					if ( response.ok ) {
						showRegisterVerify( response.payload );
					} else {
						showResult( result, response.payload.message || t( 'form_error', 'Bir sorun oluştu.' ), false );
					}
				} ).catch( function () {
					showResult( result, t( 'form_error', 'Bir sorun oluştu.' ), false );
				} ).then( function () {
					resend.disabled = false;
					resend.textContent = label;
				} );
			} );
		}

		if ( back ) {
			back.addEventListener( 'click', showRegisterForm );
		}
	}

	function initAddressCards() {
		qsa( '[data-ysf-addr-card]' ).forEach( function ( card ) {
			var form = qs( '[data-ysf-form="address"]', card );

			if ( ! form ) {
				return;
			}

			var toggle = qs( '[data-ysf-addr-toggle]', card );
			var cancel = qs( '[data-ysf-addr-cancel]', card );
			var remove = qs( '[data-ysf-addr-delete]', card );
			var line = qs( '[data-ysf-addr-line]', card );

			function setOpen( open ) {
				form.hidden = ! open;

				if ( toggle ) {
					toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				}
			}

			function paintLine( text ) {
				if ( line ) {
					line.textContent = text || t( 'acc_addr_empty', '' );
				}
			}

			if ( toggle ) {
				toggle.setAttribute( 'aria-expanded', 'false' );
				toggle.addEventListener( 'click', function () {
					setOpen( form.hidden );
				} );
			}

			if ( cancel ) {
				cancel.addEventListener( 'click', function () {
					setOpen( false );
				} );
			}

			form.addEventListener( 'submit', function ( event ) {
				event.preventDefault();

				var result = qs( '[data-ysf-result]', form );

				if ( ! form.checkValidity() ) {
					showResult( result, t( 'form_required', 'Zorunlu alanları doldurun.' ), false );
					form.reportValidity();
					return;
				}

				submitForm( form, 'ysf_save_address', collectForm( form ), function ( payload ) {
					paintLine( payload.line );
				}, { keepValues: true } );
			} );

			if ( remove ) {
				remove.addEventListener( 'click', function () {
					if ( ! window.confirm( t( 'acc_addr_del_ask', 'Adresi silmek istiyor musunuz?' ) ) ) {
						return;
					}

					var data = collectForm( form );

					data.remove = '1';

					submitForm( form, 'ysf_save_address', data, function () {
						qsa( 'input[name^="addr_"], textarea[name^="addr_"], select[name^="addr_"]', form ).forEach( function ( field ) {
							field.value = '';
						} );

						paintLine( '' );
						setOpen( false );
					}, { keepValues: true } );
				} );
			}
		} );
	}

	/**
	 * Hesabım: Mutfak / Garson / Profil sekmeleri.
	 */
	function initAccountTabs() {
		var nav = qs( '[data-ysf-acc-tabs]' );

		if ( ! nav ) {
			return;
		}

		var tabs = qsa( '[data-ysf-acc-tab]', nav );
		var panels = qsa( '[data-ysf-acc-panel]' );

		function show( id ) {
			tabs.forEach( function ( tab ) {
				var on = tab.getAttribute( 'data-ysf-acc-tab' ) === id;
				tab.classList.toggle( 'is-active', on );
				tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			} );

			panels.forEach( function ( panel ) {
				panel.hidden = panel.getAttribute( 'data-ysf-acc-panel' ) !== id;
			} );
		}

		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				var id = tab.getAttribute( 'data-ysf-acc-tab' );
				show( id );

				if ( window.history && window.history.replaceState ) {
					window.history.replaceState( null, '', '#' + ( 'kitchen' === id ? 'ysf-kitchen' : ( 'cashier' === id ? 'kasiyer' : id ) ) );
				}
			} );
		} );

		var hash = ( window.location.hash || '' ).replace( '#', '' );

		if ( 'ysf-kitchen' === hash || 'mutfak' === hash ) {
			hash = 'kitchen';
		} else if ( 'garson' === hash || 'ysf-waiter' === hash ) {
			hash = 'waiter';
		} else if ( 'kasiyer' === hash || 'ysf-cashier' === hash || 'cashier' === hash ) {
			hash = 'cashier';
		} else if ( 'profil' === hash ) {
			hash = 'profile';
		}

		var start = qs( '[data-ysf-acc-tab="' + hash + '"]', nav );

		if ( start ) {
			show( hash );
		}
	}

	/**
	 * Sipariş formunda kayıtlı adresi tek dokunuşla doldurur.
	 */
	function initKitchenDesk() {
		var root = qs( '[data-ysf-kitchen]' );

		if ( ! root ) {
			return;
		}

		var form = qs( '[data-ysf-form="kitchen"]', root );
		var flash = qs( '[data-ysf-kit-flash]', root );
		var search = qs( '[data-ysf-kit-search]', root );
		var filters = qsa( '[data-ysf-kit-filter]', root );
		var active = '';

		function field( name ) {
			return form ? qs( '[name="' + name + '"]', form ) : null;
		}

		function applyFilter() {
			var query = search ? String( search.value ).toLowerCase().trim() : '';

			qsa( '[data-ysf-kit-row]', root ).forEach( function ( row ) {
				var matchCat = ! active || row.getAttribute( 'data-cat' ) === active;
				var haystack = row.getAttribute( 'data-search' ) || '';
				var matchQuery = ! query || haystack.indexOf( query ) !== -1;

				row.hidden = ! ( matchCat && matchQuery );
			} );
		}

		function paintStock( row, sold ) {
			row.classList.toggle( 'is-soldout', sold );
			row.setAttribute( 'data-sold', sold ? '1' : '0' );

			var state = qs( '[data-ysf-kit-state]', row );
			var button = qs( '[data-ysf-kit-stock]', row );

			if ( state ) {
				state.textContent = sold ? t( 'kit_sold_out', 'Stokta yok' ) : t( 'kit_in_stock', 'Stokta' );
			}

			if ( button ) {
				button.setAttribute( 'data-task', sold ? 'in_stock' : 'sold_out' );
				button.textContent = sold ? t( 'kit_in_stock', 'Stokta' ) : t( 'kit_sold_out', 'Stokta yok' );
				button.classList.toggle( 'ysf-btn--ghost', ! sold );
			}
		}

		function parseTags( raw ) {
			try {
				var parsed = JSON.parse( raw || '[]' );
				return Array.isArray( parsed ) ? parsed : [];
			} catch ( e ) {
				return [];
			}
		}

		function currentTags() {
			var hidden = field( 'tags' );

			return hidden ? parseTags( hidden.value ) : [];
		}

		function setTags( tags ) {
			var hidden = field( 'tags' );
			var list = qs( '[data-ysf-tag-list]', form );

			if ( hidden ) {
				hidden.value = JSON.stringify( tags );
			}

			if ( ! list ) {
				return;
			}

			list.innerHTML = '';

			tags.forEach( function ( tag, index ) {
				var item = document.createElement( 'li' );
				var mark = document.createElement( 'span' );
				var remove = document.createElement( 'button' );

				item.className = 'ysf-tag ysf-tag--' + ( tag.type || 'info' );
				mark.textContent = tag.label || '';
				remove.type = 'button';
				remove.setAttribute( 'data-ysf-tag-remove', String( index ) );
				remove.setAttribute( 'aria-label', t( 'acc_cancel', 'Kaldır' ) );
				remove.textContent = '×';
				item.appendChild( mark );
				item.appendChild( remove );
				list.appendChild( item );
			} );
		}

		function fillForm( row ) {
			if ( ! form ) {
				return;
			}

			var id = field( 'id' );
			var title = field( 'title' );
			var category = field( 'category' );
			var price = field( 'price' );
			var excerpt = field( 'excerpt' );
			var sold = field( 'sold_out' );
			var orderable = field( 'orderable' );
			var photo = field( 'photo' );
			var flags = [ 'vegetarian', 'vegan', 'glutenfree', 'spicy' ];

			if ( id ) {
				id.value = row ? ( row.getAttribute( 'data-id' ) || '0' ) : '0';
			}

			if ( title ) {
				title.value = row ? ( row.getAttribute( 'data-title' ) || '' ) : '';
			}

			if ( category ) {
				category.value = row ? ( row.getAttribute( 'data-cat' ) || '0' ) : '0';
			}

			if ( price ) {
				price.value = row ? ( row.getAttribute( 'data-price' ) || '' ) : '';
			}

			if ( excerpt ) {
				excerpt.value = row ? ( row.getAttribute( 'data-excerpt' ) || '' ) : '';
			}

			if ( sold ) {
				sold.checked = ! ! ( row && '1' === row.getAttribute( 'data-sold' ) );
			}

			if ( orderable ) {
				orderable.checked = ! row || '1' === row.getAttribute( 'data-orderable' );
			}

			flags.forEach( function ( name ) {
				var box = field( name );

				if ( box ) {
					box.checked = ! ! ( row && '1' === row.getAttribute( 'data-' + name ) );
				}
			} );

			setTags( row ? parseTags( row.getAttribute( 'data-tags' ) ) : [] );

			if ( photo ) {
				photo.value = '';
			}

			form.hidden = false;
			form.scrollIntoView( { behavior: 'smooth', block: 'start' } );

			if ( title ) {
				title.focus();
			}
		}

		function runAction( id, task, onDone ) {
			request( 'ysf_kitchen_action', { id: id, task: task } ).then( function ( result ) {
				showResult( flash, result.payload.message || t( 'form_error', '' ), result.ok );

				if ( result.ok && onDone ) {
					onDone( result.payload );
				}
			} ).catch( function () {
				showResult( flash, t( 'form_error', '' ), false );
			} );
		}

		filters.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				filters.forEach( function ( other ) {
					other.classList.remove( 'is-active' );
				} );

				button.classList.add( 'is-active' );
				active = button.getAttribute( 'data-ysf-kit-filter' ) || '';
				applyFilter();
			} );
		} );

		if ( search ) {
			search.addEventListener( 'input', applyFilter );
		}

		qsa( '[data-ysf-kit-new]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				fillForm( null );
			} );
		} );

		( function initTagComposer() {
			var add = qs( '[data-ysf-tag-add]', form );
			var input = qs( '[data-ysf-tag-label]', form );
			var type = qs( '[data-ysf-tag-type]', form );
			var list = qs( '[data-ysf-tag-list]', form );

			function pushTag() {
				var label = input ? String( input.value ).trim() : '';

				if ( ! label ) {
					if ( input ) {
						input.focus();
					}
					return;
				}

				var tags = currentTags();

				if ( tags.length >= 8 ) {
					return;
				}

				tags.push( {
					label: label,
					label_en: '',
					type: type ? type.value : 'info'
				} );

				setTags( tags );

				if ( input ) {
					input.value = '';
					input.focus();
				}
			}

			if ( add ) {
				add.addEventListener( 'click', pushTag );
			}

			if ( input ) {
				input.addEventListener( 'keydown', function ( event ) {
					if ( 'Enter' === event.key ) {
						event.preventDefault();
						pushTag();
					}
				} );
			}

			if ( list ) {
				list.addEventListener( 'click', function ( event ) {
					var button = event.target.closest( '[data-ysf-tag-remove]' );

					if ( ! button ) {
						return;
					}

					var tags = currentTags();
					var index = parseInt( button.getAttribute( 'data-ysf-tag-remove' ), 10 );

					if ( ! isNaN( index ) ) {
						tags.splice( index, 1 );
						setTags( tags );
					}
				} );
			}
		}() );

		qsa( '[data-ysf-kit-cancel]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				if ( form ) {
					form.hidden = true;
				}
			} );
		} );

		qsa( '[data-ysf-kit-edit]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				fillForm( button.closest( '[data-ysf-kit-row]' ) );
			} );
		} );

		qsa( '[data-ysf-kit-stock]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var row = button.closest( '[data-ysf-kit-row]' );
				var task = button.getAttribute( 'data-task' ) || 'sold_out';

				if ( ! row ) {
					return;
				}

				button.disabled = true;

				runAction( row.getAttribute( 'data-id' ), task, function () {
					paintStock( row, 'sold_out' === task );
				} );

				window.setTimeout( function () {
					button.disabled = false;
				}, 400 );
			} );
		} );

		qsa( '[data-ysf-kit-remove]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var row = button.closest( '[data-ysf-kit-row]' );

				if ( ! row || ! window.confirm( t( 'kit_remove_ask', '' ) ) ) {
					return;
				}

				runAction( row.getAttribute( 'data-id' ), 'remove', function () {
					row.remove();
				} );
			} );
		} );

		qsa( '[data-ysf-kit-restore]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				runAction( button.getAttribute( 'data-id' ), 'restore', function () {
					window.location.reload();
				} );
			} );
		} );

		if ( form ) {
			form.addEventListener( 'submit', function ( event ) {
				event.preventDefault();

				var result = qs( '[data-ysf-result]', form );

				if ( ! form.checkValidity() ) {
					showResult( result, t( 'form_required', 'Zorunlu alanları doldurun.' ), false );
					form.reportValidity();
					return;
				}

				submitForm( form, 'ysf_kitchen_save', collectForm( form ), function ( payload ) {
					if ( payload.redirect ) {
						window.setTimeout( function () {
							window.location.href = payload.redirect;
						}, 400 );
					} else {
						window.location.reload();
					}
				}, { keepValues: true } );
			} );
		}
	}

	/**
	 * Sipariş formunda kayıtlı adresi tek dokunuşla doldurur.
	 */
	function initSavedAddressPicker() {
		var picker = qs( '[data-ysf-saved-address]' );
		var target = qs( '#ysf-order-address' );

		if ( ! picker || ! target ) {
			return;
		}

		qsa( 'input[type="radio"]', picker ).forEach( function ( radio ) {
			radio.addEventListener( 'change', function () {
				if ( ! radio.checked ) {
					return;
				}

				var line = radio.getAttribute( 'data-ysf-address-line' ) || '';

				if ( line ) {
					target.value = line;
				} else {
					target.value = '';
					target.focus();
				}
			} );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Hareket: kapak slaytı ve kaydırma animasyonları
	 * ------------------------------------------------------------------ */

	/**
	 * Kullanıcı azaltılmış hareket istiyor mu?
	 */
	function motionAllowed() {
		if ( ! window.matchMedia ) {
			return true;
		}

		return ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	/**
	 * Kapak görselleri arasında yumuşak geçiş.
	 */
	function initHeroSlider() {
		var root = qs( '[data-ysf-slider]' );

		if ( ! root ) {
			return;
		}

		var slides = qsa( '[data-ysf-slide]', root );

		if ( slides.length < 2 ) {
			return;
		}

		var dots = qsa( '[data-ysf-slide-to]' );
		var index = 0;
		var timer = null;

		function show( target ) {
			index = ( target + slides.length ) % slides.length;

			slides.forEach( function ( slide, n ) {
				slide.classList.toggle( 'is-active', n === index );
			} );

			dots.forEach( function ( dot, n ) {
				dot.classList.toggle( 'is-active', n === index );

				if ( n === index ) {
					dot.setAttribute( 'aria-current', 'true' );
				} else {
					dot.removeAttribute( 'aria-current' );
				}
			} );
		}

		function stop() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		function start() {
			stop();

			if ( ! motionAllowed() ) {
				return;
			}

			timer = window.setInterval( function () {
				show( index + 1 );
			}, 6500 );
		}

		dots.forEach( function ( dot, n ) {
			dot.addEventListener( 'click', function () {
				show( n );
				start();
			} );
		} );

		// Fare üzerindeyken ve sekme arka plandayken bekle.
		root.addEventListener( 'mouseenter', stop );
		root.addEventListener( 'mouseleave', start );

		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				stop();
			} else {
				start();
			}
		} );

		start();
	}

	/**
	 * Bölümler görünür alana girdikçe yumuşakça belirsin.
	 *
	 * Sınıf yalnızca burada eklenir; JavaScript çalışmazsa içerik olduğu gibi
	 * görünür kalır.
	 */
	function initReveal() {
		if ( ! motionAllowed() || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		var targets = qsa(
			'.ysf-section-head, .ysf-card, .ysf-fact, .ysf-menu-group, .ysf-split > *, .ysf-info-list, .ysf-map, .ysf-form'
		);

		if ( ! targets.length ) {
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}

					var el = entry.target;
					var delay = parseInt( el.getAttribute( 'data-ysf-delay' ) || '0', 10 );

					window.setTimeout( function () {
						el.classList.add( 'is-visible' );
					}, delay );

					observer.unobserve( el );
				} );
			},
			{ rootMargin: '0px 0px -8% 0px', threshold: 0.05 }
		);

		targets.forEach( function ( el ) {
			el.classList.add( 'ysf-reveal' );
			observer.observe( el );
		} );

		// Aynı satırdaki kartlar sırayla belirsin.
		qsa( '.ysf-grid, .ysf-facts' ).forEach( function ( row ) {
			qsa( '.ysf-reveal', row ).forEach( function ( el, n ) {
				el.setAttribute( 'data-ysf-delay', String( Math.min( n * 90, 450 ) ) );
			} );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Menü filtresi ve arama
	 * ------------------------------------------------------------------ */

	function initMenuFilters() {
		var filters = qsa( '[data-ysf-filter]' );
		var search = qs( '[data-ysf-menu-search]' );
		var groups = qsa( '[data-ysf-group]' );
		var items = qsa( '[data-ysf-item]' );
		var emptyBox = qs( '[data-ysf-menu-empty]' );

		if ( ! filters.length && ! search ) {
			return;
		}

		var active = 'all';

		/**
		 * Ürünü/grubu yumuşak geçişle gösterir veya gizler.
		 *
		 * @param {HTMLElement} el      Öğe.
		 * @param {boolean}     visible Görünür olacak mı.
		 * @param {number}      delay   Görünürken beklenecek süre (ms).
		 */
		function toggle( el, visible, delay ) {
			if ( ! motionAllowed() ) {
				el.classList.remove( 'is-hiding' );
				el.hidden = ! visible;

				return;
			}

			if ( visible ) {
				if ( el.hidden ) {
					el.hidden = false;
					el.classList.add( 'is-hiding' );

					window.requestAnimationFrame( function () {
						window.setTimeout( function () {
							el.classList.remove( 'is-hiding' );
						}, delay || 0 );
					} );

					return;
				}

				el.classList.remove( 'is-hiding' );

				return;
			}

			if ( el.hidden ) {
				return;
			}

			el.classList.add( 'is-hiding' );

			window.setTimeout( function () {
				// Bu arada yeniden görünür olduysa dokunma.
				if ( el.classList.contains( 'is-hiding' ) ) {
					el.hidden = true;
				}
			}, 320 );
		}

		function apply() {
			var term = search ? search.value.trim().toLowerCase() : '';
			var visibleTotal = 0;
			var shown = 0;

			groups.forEach( function ( group ) {
				var slug = group.getAttribute( 'data-ysf-group' );
				var groupVisible = 0;

				qsa( '[data-ysf-item]', group ).forEach( function ( item ) {
					var matchesCat = 'all' === active || ( item.getAttribute( 'data-cats' ) || '' ).split( ' ' ).indexOf( active ) > -1 || slug === active;
					var matchesTerm = ! term || ( item.getAttribute( 'data-search' ) || '' ).indexOf( term ) > -1;
					var visible = matchesCat && matchesTerm;

					// Görünen ürünler sırayla belirsin.
					toggle( item, visible, visible ? Math.min( shown * 40, 320 ) : 0 );

					if ( visible ) {
						groupVisible++;
						shown++;
					}
				} );

				toggle( group, groupVisible > 0, 0 );
				visibleTotal += groupVisible;
			} );

			// Grup dışında duran ürünler (ana sayfa kartları vb.).
			if ( ! groups.length ) {
				items.forEach( function ( item ) {
					var matchesTerm = ! term || ( item.getAttribute( 'data-search' ) || '' ).indexOf( term ) > -1;

					toggle( item, matchesTerm, matchesTerm ? Math.min( shown * 40, 320 ) : 0 );

					if ( matchesTerm ) {
						visibleTotal++;
						shown++;
					}
				} );
			}

			if ( emptyBox ) {
				emptyBox.hidden = visibleTotal > 0;
			}
		}

		filters.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				filters.forEach( function ( other ) {
					other.classList.remove( 'is-active' );
				} );

				button.classList.add( 'is-active' );
				active = button.getAttribute( 'data-ysf-filter' );
				apply();
			} );
		} );

		if ( search ) {
			var timer = null;

			search.addEventListener( 'input', function () {
				window.clearTimeout( timer );
				timer = window.setTimeout( apply, 160 );
			} );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Başlat
	 * ------------------------------------------------------------------ */

	function boot() {
		initNav();
		initHeader();
		initTicker();
		initStatus();
		initCart();
		initOrderForm();
		initSimpleForms();
		initPasswordToggles();
		initAccountForms();
		initAddressFields();
		initAddressCards();
		initKitchenDesk();
		initAccountTabs();
		initSavedAddressPicker();
		initMenuFilters();
		initHeroSlider();
		initReveal();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
