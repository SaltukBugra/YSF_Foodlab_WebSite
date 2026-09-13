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
					window.open( payload.whatsapp, '_blank', 'noopener' );
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
			if ( ! field.name || 'consent' === field.name ) {
				return;
			}

			if ( 'extras[]' === field.name ) {
				if ( field.checked ) {
					extras.push( field.value );
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

		var link = document.createElement( 'a' );
		link.className = 'ysf-btn ysf-btn--wa ysf-btn--sm';
		link.style.marginTop = '12px';
		link.href = url;
		link.target = '_blank';
		link.rel = 'noopener noreferrer';
		link.textContent = t( 'order_send_wa', 'WhatsApp’tan gönder' );

		box.appendChild( document.createElement( 'br' ) );
		box.appendChild( link );
	}

	function submitForm( form, action, data, onSuccess ) {
		var button = qs( '[data-ysf-submit]', form );
		var result = qs( '[data-ysf-result]', form );
		var label = button ? button.textContent : '';

		if ( button ) {
			button.disabled = true;
			button.textContent = t( 'form_sending', 'Gönderiliyor…' );
		}

		request( action, data ).then( function ( response ) {
			if ( response.ok ) {
				showResult( result, response.payload.message || t( 'form_submit', 'Gönderildi' ), true );
				form.reset();

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
