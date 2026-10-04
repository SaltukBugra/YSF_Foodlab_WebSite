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

	function revealEl( el ) {
		if ( ! el ) {
			return;
		}

		el.hidden = false;
		el.removeAttribute( 'hidden' );
		el.classList.add( 'is-open' );
	}

	function hideEl( el ) {
		if ( ! el ) {
			return;
		}

		el.hidden = true;
		el.setAttribute( 'hidden', '' );
		el.classList.remove( 'is-open' );
	}

	function totpKey( payload ) {
		if ( ! payload ) {
			return '';
		}

		return String( payload.manual || payload.secret || '' );
	}

	function paintTwoFactorQr( form, payload ) {
		var box = qs( '[data-ysf-2fa-qr-box]', form );
		var secret = qs( '[data-ysf-2fa-secret]', form );
		var key = totpKey( payload );
		var src = payload && payload.qr ? String( payload.qr ) : '';

		if ( secret ) {
			secret.textContent = key;
		}

		if ( ! box ) {
			return;
		}

		box.innerHTML = '';

		if ( ! src && payload && payload.ticket && settings.ajaxUrl ) {
			src = settings.ajaxUrl + ( settings.ajaxUrl.indexOf( '?' ) >= 0 ? '&' : '?' ) + 'action=ysf_2fa_qr&t=' + encodeURIComponent( payload.ticket );
		}

		if ( ! src ) {
			return;
		}

		var img = document.createElement( 'img' );
		img.width = 180;
		img.height = 180;
		img.alt = t( 'tfa_scan', 'Authenticator uygulamasıyla karekodu tarayın.' );
		img.src = src;
		box.appendChild( img );
	}

	function resetLoginChallenge( form, message ) {
		var creds = qs( '[data-ysf-login-creds]', form );
		var panel = qs( '[data-ysf-2fa-panel]', form );
		var box = qs( '[data-ysf-2fa]', form );
		var setup = qs( '[data-ysf-2fa-setup]', form );
		var emailBox = qs( '[data-ysf-2fa-email]', form );
		var codeField = qs( '[name="ysf_2fa_code"]', form );
		var emailField = qs( '[name="ysf_2fa_email_code"]', form );
		var ticket = qs( '[name="ysf_2fa_ticket"]', form );
		var qrBox = qs( '[data-ysf-2fa-qr-box]', form );
		var secret = qs( '[data-ysf-2fa-secret]', form );
		var card = form.closest( '.ysf-auth__card, .ysf-staff-auth__card' );
		var title = card ? qs( 'h1, h2, .ysf-auth__title', card ) : null;
		var aside = card ? qs( '[data-ysf-login-aside]', card ) : qs( '[data-ysf-login-aside]' );
		var pass = qs( '[name="password"]', form );
		var button = qs( '[data-ysf-submit]', form );
		var result = qs( '[data-ysf-result]', form );

		form.removeAttribute( 'data-ysf-2fa-active' );

		if ( creds ) {
			creds.hidden = false;
			creds.removeAttribute( 'hidden' );
		}

		if ( aside ) {
			aside.hidden = false;
			aside.removeAttribute( 'hidden' );
		}

		qsa( '[name="login"], [name="password"]', form ).forEach( function ( field ) {
			field.required = true;
		} );

		hideEl( panel );
		hideEl( box );
		hideEl( setup );
		hideEl( emailBox );

		if ( codeField ) {
			codeField.required = false;
			codeField.value = '';
		}

		if ( emailField ) {
			emailField.required = false;
			emailField.value = '';
		}

		if ( ticket ) {
			ticket.value = '';
		}

		if ( qrBox ) {
			qrBox.innerHTML = '';
		}

		if ( secret ) {
			secret.textContent = '';
		}

		if ( title ) {
			title.textContent = t( 'acc_login_title', 'Giriş yap' );
		}

		if ( button ) {
			button.disabled = false;
			button.textContent = t( 'acc_login_btn', 'Giriş yap' );
		}

		if ( pass ) {
			pass.value = '';
			pass.focus();
		}

		showResult( result, message || t( 'tfa_or_pass_wrong', 'Kod veya şifre hatalı.' ), false );
	}

	function applyTwoFactorChallenge( form, payload ) {
		var creds = qs( '[data-ysf-login-creds]', form );
		var panel = qs( '[data-ysf-2fa-panel]', form );
		var box = qs( '[data-ysf-2fa]', form );
		var setup = qs( '[data-ysf-2fa-setup]', form );
		var emailBox = qs( '[data-ysf-2fa-email]', form );
		var codeField = qs( '[name="ysf_2fa_code"]', form );
		var emailField = qs( '[name="ysf_2fa_email_code"]', form );
		var ticket = qs( '[name="ysf_2fa_ticket"]', form );
		var emailHint = qs( '[data-ysf-2fa-email-hint]', form );
		var card = form.closest( '.ysf-auth__card, .ysf-staff-auth__card' );
		var title = card ? qs( 'h1, h2, .ysf-auth__title', card ) : null;
		var aside = card ? qs( '[data-ysf-login-aside]', card ) : qs( '[data-ysf-login-aside]' );
		var submitBtn = qs( '[data-ysf-submit]', form );
		var step = payload && payload.step ? payload.step : '';
		var isEmail = '2fa_email' === step;
		var isSetup = '2fa_setup' === step;

		form.setAttribute( 'data-ysf-2fa-active', '1' );

		if ( creds ) {
			creds.hidden = true;
		}

		if ( aside ) {
			aside.hidden = true;
		}

		qsa( '[name="login"], [name="password"]', form ).forEach( function ( field ) {
			field.required = false;
		} );

		revealEl( panel );

		if ( ticket && payload && payload.ticket ) {
			ticket.value = payload.ticket;
		}

		if ( isEmail ) {
			hideEl( setup );
			hideEl( box );
			revealEl( emailBox );

			if ( emailField ) {
				emailField.required = true;
				emailField.focus();
			}

			if ( codeField ) {
				codeField.required = false;
				codeField.value = '';
			}

			if ( emailHint ) {
				emailHint.textContent = payload.email
					? t( 'tfa_email_sent', 'Doğrulama kodu %s adresine gönderildi.' ).replace( '%s', payload.email )
					: t( 'tfa_email_prompt', 'Şifre doğru. Karekod için e-postanıza gelen 6 haneli kodu yazın.' );
			}

			if ( title ) {
				title.textContent = t( 'tfa_title', 'İki adımlı doğrulama' );
			}

			if ( submitBtn ) {
				submitBtn.disabled = false;
				submitBtn.textContent = t( 'tfa_email_continue', 'E-posta kodunu doğrula' );
			}

			return;
		}

		hideEl( emailBox );

		if ( emailField ) {
			emailField.required = false;
		}

		revealEl( box );

		if ( isSetup && setup ) {
			revealEl( setup );
			paintTwoFactorQr( form, payload );
		} else {
			hideEl( setup );
		}

		if ( title ) {
			title.textContent = isSetup
				? t( 'tfa_scan', 'Authenticator uygulamasıyla karekodu tarayın.' )
				: t( 'tfa_title', 'İki adımlı doğrulama' );
		}

		if ( codeField ) {
			codeField.required = true;
			codeField.focus();
		}

		if ( submitBtn ) {
			submitBtn.disabled = false;
			submitBtn.textContent = t( 'tfa_continue', 'Kodu doğrula' );
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

	function campaignMoney( amount ) {
		return Math.round( ( Number( amount ) + 1e-8 ) * 100 ) / 100;
	}

	function campaignDiscountUnit( base, kind, value ) {
		base = Number( base ) || 0;
		value = Number( value ) || 0;

		if ( 'percent' === kind ) {
			value = Math.min( 100, Math.max( 0, value ) );
			return campaignMoney( base * ( 1 - ( value / 100 ) ) );
		}

		return campaignMoney( Math.max( 0, base - value ) );
	}

	function campaignPad( value ) {
		return ( value < 10 ? '0' : '' ) + value;
	}

	function campaignClock() {
		var now = siteNow();

		return {
			date: now.getFullYear() + '-' + campaignPad( now.getMonth() + 1 ) + '-' + campaignPad( now.getDate() ),
			hm: campaignPad( now.getHours() ) + ':' + campaignPad( now.getMinutes() )
		};
	}

	function campaignRuleLive( rule ) {
		var clock = campaignClock();

		if ( rule.start && clock.date < rule.start ) {
			return false;
		}

		if ( rule.end && clock.date > rule.end ) {
			return false;
		}

		if ( rule.timeStart && rule.timeEnd ) {
			if ( rule.end && clock.date === rule.end && clock.hm > rule.timeEnd ) {
				return false;
			}

			if ( clock.hm < rule.timeStart || clock.hm > rule.timeEnd ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sepet kalemlerine aktif kampanyayı uygular. Saklanan price liste fiyatıdır.
	 */
	function applyCampaigns( lines ) {
		var rules = ( Array.isArray( settings.campaigns ) ? settings.campaigns : [] ).filter( campaignRuleLive );
		var next = ( lines || [] ).map( function ( line ) {
			var base = Number( line.price ) || 0;

			return {
				id: Number( line.id ),
				name: line.name,
				qty: Number( line.qty ) || 0,
				size: line.size || '',
				base: base,
				price: base,
				offer: ''
			};
		} );

		next.forEach( function ( line ) {
			var best = line.base;
			var label = '';

			rules.forEach( function ( rule ) {
				var products = ( rule.products || [] ).map( Number );

				if ( products.indexOf( line.id ) === -1 ) {
					return;
				}

				if ( 'direct' !== rule.scenario && 'qty' !== rule.scenario ) {
					return;
				}

				if ( 'qty' === rule.scenario ) {
					var sum = 0;

					next.forEach( function ( other ) {
						if ( products.indexOf( other.id ) !== -1 ) {
							sum += ( Number( other.base ) || 0 ) * ( Number( other.qty ) || 0 );
						}
					} );

					if ( sum <= Number( rule.minSpend || 0 ) + 0.001 ) {
						return;
					}
				}

				var priced = campaignDiscountUnit( line.base, rule.kind, rule.value );

				if ( priced < best - 0.001 ) {
					best = priced;
					label = rule.label || '';
				}
			} );

			line.price = best;
			line.offer = label;
		} );

		rules.forEach( function ( rule ) {
			if ( 'bundle' !== rule.scenario ) {
				return;
			}

			var products = ( rule.products || [] ).map( Number );
			var indexes = [];
			var missing = false;

			products.forEach( function ( pid ) {
				var found = -1;

				next.forEach( function ( line, index ) {
					if ( found === -1 && line.id === pid && line.qty >= 1 && ! line.bundled ) {
						found = index;
					}
				} );

				if ( found === -1 ) {
					missing = true;
					return;
				}

				indexes.push( found );
			} );

			if ( missing || indexes.length < 2 ) {
				return;
			}

			var sum = 0;

			indexes.forEach( function ( index ) {
				sum += Number( next[ index ].price ) || 0;
			} );

			var target = Number( rule.bundle ) || 0;

			if ( target <= 0 || sum <= target + 0.001 ) {
				return;
			}

			var left = target;

			indexes.forEach( function ( index, position ) {
				var line = next[ index ];
				var unit = Number( line.price ) || 0;
				var bundledUnit;

				if ( position === indexes.length - 1 ) {
					bundledUnit = campaignMoney( left );
				} else {
					bundledUnit = campaignMoney( unit / sum * target );
					left = campaignMoney( left - bundledUnit );
				}

				var qty = Number( line.qty ) || 1;

				line.price = campaignMoney( ( bundledUnit + ( ( qty - 1 ) * unit ) ) / qty );
				line.offer = rule.label || line.offer;
				line.bundled = true;
			} );
		} );

		next.forEach( function ( line ) {
			delete line.bundled;
		} );

		return next;
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

	function refreshNonce() {
		var body = new FormData();

		body.append( 'action', 'ysf_nonce' );

		return fetch( settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} ).then( function ( response ) {
			return response.json();
		} ).then( function ( json ) {
			if ( json && json.success && json.data && json.data.nonce ) {
				settings.nonce = json.data.nonce;
				return true;
			}

			return false;
		} ).catch( function () {
			return false;
		} );
	}

	function request( action, data, retried ) {
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

				// Önbellekteki sayfanın güvenlik anahtarı eskidiyse yenileyip bir kez daha dene.
				if ( 403 === response.status && '-1' === String( text ).trim() && ! retried ) {
					return refreshNonce().then( function ( fresh ) {
						return fresh ? request( action, data, true ) : {
							ok: false,
							payload: { message: t( 'form_error', 'Bir sorun oluştu. Lütfen tekrar deneyin veya bizi arayın.' ) }
						};
					} );
				}

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
			var visible = items.filter( function ( item ) {
				return ! item.classList.contains( 'is-off' );
			} );

			if ( ! visible.length ) {
				return;
			}

			items.forEach( function ( item ) {
				item.classList.remove( 'is-active' );
			} );

			var current = visible.indexOf( items[ index ] );
			var next = visible[ ( Math.max( 0, current ) + 1 ) % visible.length ];

			index = items.indexOf( next );
			next.classList.add( 'is-active' );
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

	function pricedCart() {
		return applyCampaigns( readCart() );
	}

	function cartSubtotal() {
		return pricedCart().reduce( function ( sum, line ) {
			return sum + ( Number( line.price ) * Number( line.qty ) );
		}, 0 );
	}

	function cartListTotal() {
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

	function lineKey( line ) {
		return String( line.id ) + '|' + String( line.size || '' );
	}

	function addToCart( item ) {
		var cart = readCart();
		var found = false;
		var key = lineKey( item );

		cart = cart.map( function ( line ) {
			if ( lineKey( line ) === key ) {
				found = true;
				line.qty = Number( line.qty ) + 1;
			}

			return line;
		} );

		if ( ! found ) {
			cart.push( {
				id: Number( item.id ),
				name: item.name,
				price: Number( item.price ),
				qty: 1,
				size: item.size || ''
			} );
		}

		writeCart( cart );
	}

	function setQty( key, qty ) {
		var cart = readCart().map( function ( line ) {
			if ( lineKey( line ) === String( key ) ) {
				line.qty = Math.max( 0, Math.min( 50, qty ) );
			}

			return line;
		} ).filter( function ( line ) {
			return line.qty > 0;
		} );

		writeCart( cart );
	}

	var cartReturnFocus = null;

	function focusables( scope ) {
		return qsa( 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])', scope ).filter( function ( el ) {
			return ! el.hidden && null !== el.offsetParent;
		} );
	}

	function cartKeydown( event ) {
		var panel = qs( '[data-ysf-cart]' );

		if ( ! panel || ! panel.classList.contains( 'is-open' ) ) {
			return;
		}

		if ( 'Escape' === event.key ) {
			event.preventDefault();
			closeCart();
			return;
		}

		if ( 'Tab' !== event.key ) {
			return;
		}

		var list = focusables( panel );

		if ( ! list.length ) {
			event.preventDefault();
			panel.focus();
			return;
		}

		var first = list[ 0 ];
		var last = list[ list.length - 1 ];

		if ( event.shiftKey && ( document.activeElement === first || document.activeElement === panel ) ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	function openCart() {
		var panel = qs( '[data-ysf-cart]' );
		var scrim = qs( '[data-ysf-scrim]' );
		var button = qs( '[data-ysf-cart-open]' );

		if ( ! panel ) {
			return;
		}

		if ( ! panel.classList.contains( 'is-open' ) ) {
			cartReturnFocus = document.activeElement;
		}

		panel.classList.add( 'is-open' );
		panel.setAttribute( 'aria-hidden', 'false' );
		document.addEventListener( 'keydown', cartKeydown );

		if ( button ) {
			button.setAttribute( 'aria-expanded', 'true' );
		}

		if ( scrim ) {
			scrim.classList.add( 'is-visible' );
		}

		window.setTimeout( function () {
			var close = qs( '[data-ysf-cart-close]', panel );
			( close || panel ).focus();
		}, 60 );
	}

	function closeCart() {
		var panel = qs( '[data-ysf-cart]' );
		var scrim = qs( '[data-ysf-scrim]' );
		var button = qs( '[data-ysf-cart-open]' );

		if ( ! panel ) {
			return;
		}

		var wasOpen = panel.classList.contains( 'is-open' );

		panel.classList.remove( 'is-open' );
		panel.setAttribute( 'aria-hidden', 'true' );
		document.removeEventListener( 'keydown', cartKeydown );

		if ( button ) {
			button.setAttribute( 'aria-expanded', 'false' );
		}

		if ( scrim ) {
			scrim.classList.remove( 'is-visible' );
		}

		if ( wasOpen && cartReturnFocus && 'function' === typeof cartReturnFocus.focus && document.contains( cartReturnFocus ) ) {
			cartReturnFocus.focus();
		}

		cartReturnFocus = null;
	}

	function qtyControl( line ) {
		var wrap = document.createElement( 'div' );
		wrap.className = 'ysf-qty';

		var minus = document.createElement( 'button' );
		minus.type = 'button';
		minus.textContent = '−';
		minus.setAttribute( 'aria-label', t( 'qty_less', 'Bir azalt: %s' ).replace( '%s', line.name ) );
		minus.addEventListener( 'click', function () {
			setQty( lineKey( line ), Number( line.qty ) - 1 );
		} );

		var output = document.createElement( 'output' );
		output.textContent = String( line.qty );
		output.setAttribute( 'aria-label', t( 'qty_label', 'Adet' ) );

		var plus = document.createElement( 'button' );
		plus.type = 'button';
		plus.textContent = '+';
		plus.setAttribute( 'aria-label', t( 'qty_more', 'Bir artır: %s' ).replace( '%s', line.name ) );
		plus.addEventListener( 'click', function () {
			setQty( lineKey( line ), Number( line.qty ) + 1 );
		} );

		wrap.appendChild( minus );
		wrap.appendChild( output );
		wrap.appendChild( plus );

		return wrap;
	}

	function buildLines( container ) {
		var cart = pricedCart();

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

			if ( Number( line.base ) > Number( line.price ) + 0.001 ) {
				var struck = document.createElement( 'del' );
				struck.textContent = formatPrice( Number( line.base ) * Number( line.qty ) );
				price.appendChild( struck );
				price.appendChild( document.createTextNode( formatPrice( Number( line.price ) * Number( line.qty ) ) ) );
			} else {
				price.textContent = formatPrice( Number( line.price ) * Number( line.qty ) );
			}

			if ( line.offer ) {
				var offer = document.createElement( 'span' );
				offer.className = 'ysf-campaign-hint';
				offer.textContent = line.offer;
				name.appendChild( document.createElement( 'br' ) );
				name.appendChild( offer );
			}

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

		var listed = cartListTotal();
		var saving = listed - subtotal;

		row( t( 'subtotal', 'Ara toplam' ), formatPrice( saving > 0.01 ? listed : subtotal ) );

		if ( saving > 0.01 ) {
			row( t( 'campaign_discount', 'Kampanya indirimi' ), '−' + formatPrice( saving ) );
		}

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
				var card = button.closest( '[data-ysf-item]' );
				var select = card ? card.querySelector( '[data-ysf-size]' ) : null;
				var item = {
					id: button.getAttribute( 'data-id' ),
					name: button.getAttribute( 'data-name' ),
					price: button.getAttribute( 'data-price' ),
					size: ''
				};

				if ( select && select.value ) {
					var option = select.options[ select.selectedIndex ];
					item.size = select.value;
					item.price = option.getAttribute( 'data-price' ) || item.price;
					item.name = item.name + ' (' + ( option.getAttribute( 'data-label' ) || '' ) + ')';
				}

				addToCart( item );

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

				if ( line.size ) {
					var match = null;
					( fresh.sizes || [] ).forEach( function ( size ) {
						if ( size.key === line.size ) {
							match = size;
						}
					} );

					if ( ! match ) {
						changed = true;
						return false;
					}

					var named = fresh.name + ' (' + match.label + ')';

					if ( Number( match.price ) !== Number( line.price ) || named !== line.name ) {
						line.price = Number( match.price );
						line.name = named;
						changed = true;
					}

					return true;
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

		var seatedAt = currentTable();
		var tableRadio = qs( 'input[name="type"][value="table"]', form );
		var tableInput = qs( '[name="table"]', form );

		if ( seatedAt && tableRadio && tableInput ) {
			tableRadio.checked = true;
			tableInput.value = seatedAt;
		}

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
				return { id: line.id, qty: line.qty, size: line.size || '' };
			} ) );

			submitForm( form, 'ysf_submit_order', data, function ( payload ) {
				writeCart( [] );

				if ( payload.trackUrl ) {
					rememberOrder( payload.orderId, payload.trackUrl );
					appendTrackLink( qs( '[data-ysf-result]', form ), payload.trackUrl );
				}

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
				var handled = options && 'function' === typeof options.onError && options.onError( response.payload );

				if ( ! handled ) {
					showResult( result, response.payload.message || t( 'form_error', 'Bir sorun oluştu.' ), false );
				}
			}
		} ).catch( function () {
			showResult( result, t( 'form_error', 'Bir sorun oluştu.' ), false );
		} ).then( function () {
			if ( button && ! form.getAttribute( 'data-ysf-2fa-active' ) ) {
				button.disabled = false;
				button.textContent = label;
			} else if ( button ) {
				button.disabled = false;
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

				submitForm( form, map[ name ], collectForm( form ), function ( payload ) {
					if ( 'reservation' === name && payload.calendar ) {
						appendCalendarButton( result, payload.calendar );
					}
				} );
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
		var emailEl = qs( '[data-ysf-verify-email]' );
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

		if ( emailEl ) {
			emailEl.textContent = payload && payload.email ? payload.email : '';
		}

		if ( codeField ) {
			codeField.value = '';
			codeField.focus();
		}

		if ( verifyResult && payload && payload.message ) {
			showResult( verifyResult, payload.message, ! payload.mailFailed );
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

					if ( 'login' === name && payload && ( '2fa' === payload.step || '2fa_setup' === payload.step || '2fa_email' === payload.step || payload.ticket ) ) {
						applyTwoFactorChallenge( form, payload );
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
				}, {
					keepValues: 'profile' === name || 'register' === name || 'verify' === name || 'resetpass' === name || 'login' === name,
					onError: 'login' === name ? function ( payload ) {
						if ( payload && ( payload.reset || form.getAttribute( 'data-ysf-2fa-active' ) ) ) {
							resetLoginChallenge( form, payload.message );
							return true;
						}

						return false;
					} : null
				} );
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
					var hash = 'kitchen' === id ? 'ysf-kitchen' : ( 'cashier' === id ? 'kasiyer' : ( 'campaigns' === id ? 'kampanya' : ( 'announcements' === id ? 'duyuru' : id ) ) );
					window.history.replaceState( null, '', '#' + hash );
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
		} else if ( 'kampanya' === hash || 'campaigns' === hash || 'ysf-campaigns' === hash ) {
			hash = 'campaigns';
		} else if ( 'duyuru' === hash || 'announcements' === hash || 'ysf-announcements' === hash ) {
			hash = 'announcements';
		} else if ( 'profil' === hash ) {
			hash = 'profile';
		}

		var start = qs( '[data-ysf-acc-tab="' + hash + '"]', nav );

		if ( start ) {
			show( hash );
		}
	}

	/**
	 * Hesabım kampanya formu. Yalnızca yönetici sekmesinde vardır.
	 */
	function initAccountCampaigns() {
		var root = qs( '[data-ysf-campaigns]' );
		var form = root ? qs( '[data-ysf-form="campaign"]', root ) : null;

		if ( ! root || ! form ) {
			return;
		}

		var scenario = qs( '[data-ysf-camp-scenario]', form );

		function syncScenario() {
			var value = scenario ? scenario.value : 'direct';

			qsa( '[data-ysf-camp]', form ).forEach( function ( node ) {
				var slot = node.getAttribute( 'data-ysf-camp' );
				var show = true;

				if ( 'products' === slot ) {
					show = 'general' !== value;
				}

				if ( 'discount' === slot ) {
					show = 'direct' === value || 'qty' === value;
				}

				if ( 'min' === slot ) {
					show = 'qty' === value;
				}

				if ( 'bundle' === slot ) {
					show = 'bundle' === value;
				}

				node.hidden = ! show;
			} );
		}

		function productBoxes() {
			var box = qs( '[data-ysf-camp-products]', form );

			return box ? qsa( 'input[type="checkbox"]', box ) : [];
		}

		function setChecks( ids ) {
			var chosen = {};

			( ids || [] ).forEach( function ( id ) {
				chosen[ String( id ) ] = true;
			} );

			productBoxes().forEach( function ( box ) {
				box.checked = !! chosen[ box.value ];
			} );
		}

		function openForm( data ) {
			form.hidden = false;
			form.reset();

			var record = data || {};
			var idField = qs( '[name="id"]', form );

			if ( idField ) {
				idField.value = record.id ? String( record.id ) : '0';
			}

			[ 'title', 'excerpt', 'title_en', 'excerpt_en', 'scenario', 'kind', 'value', 'min', 'bundle', 'start', 'end', 'time_start', 'time_end' ].forEach( function ( name ) {
				var input = qs( '[name="' + name + '"]', form );

				if ( input && 'undefined' !== typeof record[ name ] && null !== record[ name ] ) {
					input.value = String( record[ name ] );
				}
			} );

			setChecks( record.products || [] );
			syncScenario();

			var photo = qs( '[data-ysf-camp-photo]', form );
			var file = qs( '[name="photo"]', form );

			if ( file ) {
				file.value = '';
			}

			if ( photo ) {
				if ( record && record.thumb ) {
					photo.hidden = false;
					photo.src = record.thumb;
				} else {
					photo.hidden = true;
					photo.removeAttribute( 'src' );
				}
			}

			form.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}

		if ( scenario ) {
			scenario.addEventListener( 'change', syncScenario );
		}

		syncScenario();

		var productSearch = qs( '[data-ysf-camp-product-search]', form );

		if ( productSearch ) {
			productSearch.addEventListener( 'input', function () {
				var query = productSearch.value.toLocaleLowerCase( 'tr' ).trim();

				qsa( '[data-ysf-camp-group]', form ).forEach( function ( group ) {
					var visible = 0;

					qsa( '[data-ysf-camp-product]', group ).forEach( function ( row ) {
						var name = ( row.getAttribute( 'data-name' ) || '' ).toLocaleLowerCase( 'tr' );
						var show = ! query || name.indexOf( query ) !== -1;

						row.hidden = ! show;

						if ( show ) {
							visible++;
						}
					} );

					group.hidden = ! visible;
				} );
			} );
		}

		var add = qs( '[data-ysf-camp-new]', root );

		if ( add ) {
			add.addEventListener( 'click', function () {
				openForm( null );
			} );
		}

		var cancel = qs( '[data-ysf-camp-cancel]', form );

		if ( cancel ) {
			cancel.addEventListener( 'click', function () {
				form.hidden = true;
			} );
		}

		qsa( '[data-ysf-camp-edit]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var row = button.closest( '[data-ysf-camp-row]' );
				var raw = row ? row.getAttribute( 'data-campaign' ) : '';
				var data = null;

				try {
					data = raw ? JSON.parse( raw ) : null;
				} catch ( e ) {
					data = null;
				}

				openForm( data );
			} );
		} );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var result = qs( '[data-ysf-result]', form );

			if ( ! form.checkValidity() ) {
				showResult( result, t( 'form_required', 'Zorunlu alanları doldurun.' ), false );
				form.reportValidity();
				return;
			}

			var data = {};

			qsa( 'input, textarea, select', form ).forEach( function ( field ) {
				if ( ! field.name || 'checkbox' === field.type || 'radio' === field.type ) {
					return;
				}

				if ( 'file' === field.type ) {
					if ( field.files && field.files[ 0 ] ) {
						data[ field.name ] = field.files[ 0 ];
					}
					return;
				}

				data[ field.name ] = field.value;
			} );

			var products = [];

			productBoxes().forEach( function ( box ) {
				if ( box.checked ) {
					products.push( box.value );
				}
			} );

			data.products_json = JSON.stringify( products );

			submitForm( form, 'ysf_account_save_campaign', data, function () {
				window.location.hash = 'kampanya';
				window.location.reload();
			}, { keepValues: true } );
		} );

		qsa( '[data-ysf-camp-delete]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				if ( ! window.confirm( t( 'camp_delete_ask', 'Bu kampanyayı kalıcı olarak silmek istiyor musunuz?' ) ) ) {
					return;
				}

				button.disabled = true;

				request( 'ysf_account_delete_campaign', { id: button.getAttribute( 'data-id' ) } ).then( function ( response ) {
					if ( response.ok ) {
						window.location.hash = 'kampanya';
						window.location.reload();
						return;
					}

					button.disabled = false;
					window.alert( response.payload.message || t( 'form_error', 'Bir sorun oluştu.' ) );
				} );
			} );
		} );
	}

	/**
	 * Hesabım duyuru formu. Yalnızca yönetici sekmesinde vardır.
	 */
	function initAccountAnnouncements() {
		var root = qs( '[data-ysf-announcements]' );
		var form = root ? qs( '[data-ysf-form="announcement"]', root ) : null;

		if ( ! root || ! form ) {
			return;
		}

		function openForm( data ) {
			form.hidden = false;
			form.reset();

			var record = data || {};
			var idField = qs( '[name="id"]', form );

			if ( idField ) {
				idField.value = record.id ? String( record.id ) : '0';
			}

			[ 'title', 'excerpt', 'content', 'start', 'end' ].forEach( function ( name ) {
				var input = qs( '[name="' + name + '"]', form );

				if ( input && 'undefined' !== typeof record[ name ] && null !== record[ name ] ) {
					input.value = String( record[ name ] );
				}
			} );

			var showBar = qs( '[name="show_bar"]', form );

			if ( showBar ) {
				showBar.checked = ! record.id || !! record.show_bar;
			}

			var photo = qs( '[data-ysf-ann-photo]', form );
			var file = qs( '[name="photo"]', form );

			if ( file ) {
				file.value = '';
			}

			if ( photo ) {
				if ( record && record.thumb ) {
					photo.hidden = false;
					photo.src = record.thumb;
				} else {
					photo.hidden = true;
					photo.removeAttribute( 'src' );
				}
			}

			form.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}

		var add = qs( '[data-ysf-ann-new]', root );

		if ( add ) {
			add.addEventListener( 'click', function () {
				openForm( null );
			} );
		}

		var cancel = qs( '[data-ysf-ann-cancel]', form );

		if ( cancel ) {
			cancel.addEventListener( 'click', function () {
				form.hidden = true;
			} );
		}

		qsa( '[data-ysf-ann-edit]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var row = button.closest( '[data-ysf-ann-row]' );
				var raw = row ? row.getAttribute( 'data-announcement' ) : '';
				var data = null;

				try {
					data = raw ? JSON.parse( raw ) : null;
				} catch ( e ) {
					data = null;
				}

				openForm( data );
			} );
		} );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var result = qs( '[data-ysf-result]', form );

			if ( ! form.checkValidity() ) {
				showResult( result, t( 'form_required', 'Zorunlu alanları doldurun.' ), false );
				form.reportValidity();
				return;
			}

			var data = {};

			qsa( 'input, textarea, select', form ).forEach( function ( field ) {
				if ( ! field.name ) {
					return;
				}

				if ( 'checkbox' === field.type ) {
					if ( field.checked ) {
						data[ field.name ] = field.value;
					}
					return;
				}

				if ( 'radio' === field.type ) {
					return;
				}

				if ( 'file' === field.type ) {
					if ( field.files && field.files[ 0 ] ) {
						data[ field.name ] = field.files[ 0 ];
					}
					return;
				}

				data[ field.name ] = field.value;
			} );

			submitForm( form, 'ysf_account_save_announcement', data, function () {
				window.location.hash = 'duyuru';
				window.location.reload();
			}, { keepValues: true } );
		} );

		qsa( '[data-ysf-ann-delete]', root ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				if ( ! window.confirm( t( 'ann_delete_ask', 'Bu duyuruyu kalıcı olarak silmek istiyor musunuz?' ) ) ) {
					return;
				}

				button.disabled = true;

				request( 'ysf_account_delete_announcement', { id: button.getAttribute( 'data-id' ) } ).then( function ( response ) {
					if ( response.ok ) {
						window.location.hash = 'duyuru';
						window.location.reload();
						return;
					}

					button.disabled = false;
					window.alert( response.payload.message || t( 'form_error', 'Bir sorun oluştu.' ) );
				} );
			} );
		} );
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

		function sizeInput( name, value, placeholder, type ) {
			var input = document.createElement( 'input' );
			input.type = type || 'text';
			input.value = value || '';
			input.placeholder = placeholder;
			input.setAttribute( 'data-ysf-size-' + name, '' );
			input.setAttribute( 'maxlength', '24' );

			if ( 'price' === name ) {
				input.inputMode = 'decimal';
				input.min = '0';
				input.step = '0.01';
				input.removeAttribute( 'maxlength' );
			}

			input.addEventListener( 'input', syncSizes );
			return input;
		}

		function sizeRow( size ) {
			var row = document.createElement( 'div' );
			var remove = document.createElement( 'button' );

			row.className = 'ysf-kit-sizes__row';
			row.setAttribute( 'data-ysf-size-row', '' );
			row.appendChild( sizeInput( 'label', size.label, t( 'kit_size_name', 'Ebat' ) ) );
			row.appendChild( sizeInput( 'en', size.label_en, 'EN' ) );
			row.appendChild( sizeInput( 'price', size.price, t( 'kit_price', 'Fiyat' ), 'number' ) );
			remove.type = 'button';
			remove.className = 'ysf-link-btn';
			remove.textContent = '×';
			remove.setAttribute( 'data-ysf-size-remove', '' );
			row.appendChild( remove );
			return row;
		}

		function readSizeRows() {
			return qsa( '[data-ysf-size-row]', form ).map( function ( row ) {
				var label = qs( '[data-ysf-size-label]', row );
				var en = qs( '[data-ysf-size-en]', row );
				var price = qs( '[data-ysf-size-price]', row );

				return {
					label: label ? label.value : '',
					label_en: en ? en.value : '',
					price: price ? price.value : ''
				};
			} );
		}

		function syncSizes() {
			var hidden = field( 'sizes' );

			if ( hidden ) {
				hidden.value = JSON.stringify( readSizeRows() );
			}
		}

		function setSizes( sizes ) {
			var rows = qs( '[data-ysf-size-rows]', form );
			var list = Array.isArray( sizes ) && sizes.length ? sizes : [ { label: '', label_en: '', price: '' } ];

			if ( ! rows ) {
				syncSizes();
				return;
			}

			rows.textContent = '';
			list.forEach( function ( size ) {
				rows.appendChild( sizeRow( size || {} ) );
			} );
			syncSizes();
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
			setSizes( row ? parseTags( row.getAttribute( 'data-sizes' ) ) : [] );

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

		( function () {
			var add = qs( '[data-ysf-size-add]', form );
			var rows = qs( '[data-ysf-size-rows]', form );

			if ( add ) {
				add.addEventListener( 'click', function () {
					if ( ! rows ) {
						return;
					}

					rows.appendChild( sizeRow( {} ) );
					syncSizes();
				} );
			}

			if ( rows ) {
				rows.addEventListener( 'click', function ( event ) {
					var button = event.target.closest( '[data-ysf-size-remove]' );
					var row = button ? button.closest( '[data-ysf-size-row]' ) : null;

					if ( ! row ) {
						return;
					}

					if ( qsa( '[data-ysf-size-row]', form ).length > 1 ) {
						row.remove();
					} else {
						qsa( 'input', row ).forEach( function ( input ) {
							input.value = '';
						} );
					}

					syncSizes();
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
	 * Menü kategorileri (Scrollspy), filtreler ve arama
	 * ------------------------------------------------------------------ */

	function initMenuFilters() {
		var catLinks = qsa( '[data-ysf-cat-link]' );
		var filters = qsa( '[data-ysf-filter]' );
		var search = qs( '[data-ysf-menu-search]' );
		var groups = qsa( '[data-ysf-group]' );
		var items = qsa( '[data-ysf-item]' );
		var emptyBox = qs( '[data-ysf-menu-empty]' );

		if ( ! catLinks.length && ! filters.length && ! search && ! qsa( '[data-ysf-diet]' ).length ) {
			return;
		}

		var activeFilter = 'all';
		var diets = [];
		var favOnly = false;
		var dietButtons = qsa( '[data-ysf-diet]' );
		var favButton = qs( '[data-ysf-fav-filter]' );
		var isProgrammaticScroll = false;
		var scrollTimer = null;

		function matchesExtras( item ) {
			var tags = ( item.getAttribute( 'data-diet' ) || '' ).split( ' ' );

			for ( var i = 0; i < diets.length; i++ ) {
				if ( 'nospicy' === diets[ i ] ) {
					if ( tags.indexOf( 'spicy' ) > -1 ) {
						return false;
					}
				} else if ( tags.indexOf( diets[ i ] ) === -1 ) {
					return false;
				}
			}

			return ! favOnly || isFavorite( item.getAttribute( 'data-id' ) );
		}

		/**
		 * Ürünü/grubu yumuşak geçişle gösterir veya gizler.
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
				if ( el.classList.contains( 'is-hiding' ) ) {
					el.hidden = true;
				}
			}, 320 );
		}

		function getNavOffset() {
			var header = qs( '.ysf-header' );
			var headerHeight = header ? header.offsetHeight : 72;
			var catNav = qs( '[data-ysf-cat-nav]' );
			var navHeight = 0;

			if ( catNav ) {
				var comp = window.getComputedStyle( catNav );
				if ( 'sticky' === comp.position && catNav.offsetHeight < 120 ) {
					navHeight = catNav.offsetHeight;
				}
			}

			return headerHeight + navHeight + 16;
		}

		function setActiveCategory( slug ) {
			var activeLink = null;

			catLinks.forEach( function ( link ) {
				var isCurrent = link.getAttribute( 'data-ysf-cat-link' ) === slug;
				link.classList.toggle( 'is-active', isCurrent );
				link.setAttribute( 'aria-selected', isCurrent ? 'true' : 'false' );

				if ( isCurrent ) {
					activeLink = link;
				}
			} );

			// Mobilde yatay kayan çubukta aktif kategoriyi ortala
			if ( activeLink && activeLink.parentElement ) {
				var list = activeLink.parentElement;
				if ( list.scrollWidth > list.clientWidth ) {
					var left = activeLink.offsetLeft;
					var width = activeLink.offsetWidth;
					var containerWidth = list.clientWidth;
					var scrollLeft = left - ( containerWidth / 2 ) + ( width / 2 );

					if ( 'scrollTo' in list ) {
						list.scrollTo( {
							left: Math.max( 0, scrollLeft ),
							behavior: 'smooth'
						} );
					} else {
						list.scrollLeft = Math.max( 0, scrollLeft );
					}
				}
			}
		}

		function scrollToCategory( target ) {
			var offset = getNavOffset();
			var rect = target.getBoundingClientRect();
			var targetY = rect.top + window.pageYOffset - offset;

			isProgrammaticScroll = true;

			if ( 'scrollBehavior' in document.documentElement.style ) {
				window.scrollTo( {
					top: Math.max( 0, targetY ),
					behavior: 'smooth'
				} );
			} else {
				window.scrollTo( 0, Math.max( 0, targetY ) );
			}

			window.clearTimeout( scrollTimer );
			scrollTimer = window.setTimeout( function () {
				isProgrammaticScroll = false;
				updateScrollspy();
			}, 750 );
		}

		function updateScrollspy() {
			if ( isProgrammaticScroll ) {
				return;
			}

			var visibleGroups = groups.filter( function ( g ) {
				return ! g.hidden && g.offsetHeight > 0;
			} );

			if ( ! visibleGroups.length ) {
				return;
			}

			var offset = getNavOffset() + 32;
			var activeSlug = null;

			var atBottom = ( window.innerHeight + window.pageYOffset ) >= ( document.documentElement.scrollHeight - 70 );
			if ( atBottom ) {
				activeSlug = visibleGroups[ visibleGroups.length - 1 ].getAttribute( 'data-ysf-group' );
			} else {
				for ( var i = 0; i < visibleGroups.length; i++ ) {
					var rect = visibleGroups[ i ].getBoundingClientRect();
					if ( rect.top <= offset ) {
						activeSlug = visibleGroups[ i ].getAttribute( 'data-ysf-group' );
					} else {
						break;
					}
				}
				if ( ! activeSlug && visibleGroups.length ) {
					activeSlug = visibleGroups[ 0 ].getAttribute( 'data-ysf-group' );
				}
			}

			if ( activeSlug ) {
				setActiveCategory( activeSlug );
			}
		}

		function apply() {
			var term = search ? search.value.trim().toLowerCase() : '';
			var visibleTotal = 0;
			var shown = 0;

			groups.forEach( function ( group ) {
				var slug = group.getAttribute( 'data-ysf-group' );
				var groupVisible = 0;

				qsa( '[data-ysf-item]', group ).forEach( function ( item ) {
					var matchesCat = 'all' === activeFilter || ( item.getAttribute( 'data-cats' ) || '' ).split( ' ' ).indexOf( activeFilter ) > -1 || slug === activeFilter;
					var matchesTerm = ! term || ( item.getAttribute( 'data-search' ) || '' ).indexOf( term ) > -1;
					var visible = matchesCat && matchesTerm && matchesExtras( item );

					toggle( item, visible, visible ? Math.min( shown * 40, 320 ) : 0 );

					if ( visible ) {
						groupVisible++;
						shown++;
					}
				} );

				toggle( group, groupVisible > 0, 0 );

				// İlgili kategori bağlantısının görünürlüğünü güncelle
				var navLink = qs( '[data-ysf-cat-link="' + slug + '"]' );
				if ( navLink ) {
					navLink.hidden = groupVisible === 0;
				}

				visibleTotal += groupVisible;
			} );

			// Grup dışında duran ürünler
			if ( ! groups.length ) {
				items.forEach( function ( item ) {
					var matchesTerm = ( ! term || ( item.getAttribute( 'data-search' ) || '' ).indexOf( term ) > -1 ) && matchesExtras( item );

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

			window.setTimeout( updateScrollspy, 350 );
		}

		// Kategori bağlantılarına tıklama (Smooth Scroll)
		catLinks.forEach( function ( link ) {
			link.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var slug = link.getAttribute( 'data-ysf-cat-link' );
				var target = qs( '[data-ysf-group="' + slug + '"]' );

				if ( ! target ) {
					return;
				}

				setActiveCategory( slug );
				scrollToCategory( target );

				if ( window.history && window.history.replaceState ) {
					window.history.replaceState( null, '', '#kategori-' + slug );
				}
			} );
		} );

		// Eski tip filtre butonları desteği
		filters.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				filters.forEach( function ( other ) {
					other.classList.remove( 'is-active' );
					other.setAttribute( 'aria-pressed', 'false' );
				} );

				button.classList.add( 'is-active' );
				button.setAttribute( 'aria-pressed', 'true' );
				activeFilter = button.getAttribute( 'data-ysf-filter' );
				apply();
			} );
		} );

		dietButtons.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var key = button.getAttribute( 'data-ysf-diet' );
				var on = diets.indexOf( key ) === -1;

				diets = diets.filter( function ( d ) {
					return d !== key;
				} );

				if ( on ) {
					diets.push( key );
				}

				button.classList.toggle( 'is-active', on );
				button.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
				apply();
			} );
		} );

		if ( favButton ) {
			favButton.hidden = ! readFavorites().length;

			favButton.addEventListener( 'click', function () {
				favOnly = ! favOnly;
				favButton.classList.toggle( 'is-active', favOnly );
				favButton.setAttribute( 'aria-pressed', favOnly ? 'true' : 'false' );
				apply();
			} );

			document.addEventListener( 'ysf:favorites', function () {
				favButton.hidden = ! readFavorites().length;

				if ( favButton.hidden && favOnly ) {
					favOnly = false;
					favButton.classList.remove( 'is-active' );
					favButton.setAttribute( 'aria-pressed', 'false' );
				}

				if ( favOnly ) {
					apply();
				}
			} );
		}

		if ( search ) {
			var timer = null;

			search.addEventListener( 'input', function () {
				window.clearTimeout( timer );
				timer = window.setTimeout( apply, 160 );
			} );
		}

		// Scrollspy olayları
		var scrollRaf = null;
		function onWindowScroll() {
			if ( isProgrammaticScroll ) {
				return;
			}
			if ( scrollRaf ) {
				return;
			}
			scrollRaf = window.requestAnimationFrame( function () {
				scrollRaf = null;
				updateScrollspy();
			} );
		}

		window.addEventListener( 'scroll', onWindowScroll, { passive: true } );
		window.addEventListener( 'resize', onWindowScroll, { passive: true } );

		// Sayfa hash ile yüklendiyse ilgili kategoriye kay
		if ( window.location.hash && window.location.hash.indexOf( '#kategori-' ) === 0 ) {
			var initSlug = window.location.hash.replace( '#kategori-', '' );
			var initTarget = qs( '[data-ysf-group="' + initSlug + '"]' );
			if ( initTarget ) {
				window.setTimeout( function () {
					setActiveCategory( initSlug );
					scrollToCategory( initTarget );
				}, 220 );
			}
		} else {
			updateScrollspy();
		}
	}

	/**
	 * Duyuru şeridi ve ana sayfa kartı, saat aralığına göre güncellenir.
	 * Önbellekte kalan sayfa da restoranın saatine uyar.
	 */
	function initCampaignNotices() {
		var nodes = qsa( '[data-ysf-camp-notice]' );

		if ( ! nodes.length ) {
			return;
		}

		function modeOf( node ) {
			var clock = campaignClock();
			var start = node.getAttribute( 'data-start' ) || '';
			var end = node.getAttribute( 'data-end' ) || '';
			var timeStart = node.getAttribute( 'data-time-start' ) || '';
			var timeEnd = node.getAttribute( 'data-time-end' ) || '';

			if ( start && clock.date < start ) {
				return 'hidden';
			}

			if ( end && clock.date > end ) {
				return 'expired';
			}

			if ( timeStart && timeEnd ) {
				if ( end && clock.date === end && clock.hm > timeEnd ) {
					return 'expired';
				}

				if ( clock.hm > timeEnd && end && clock.date < end ) {
					return 'tomorrow';
				}

				if ( clock.hm < timeStart ) {
					return 'pending';
				}

				if ( clock.hm > timeEnd ) {
					return 'tomorrow';
				}
			}

			return 'live';
		}

		function paint() {
			nodes.forEach( function ( node ) {
				var mode = modeOf( node );
				var title = node.getAttribute( 'data-title' ) || '';
				var timeStart = node.getAttribute( 'data-time-start' ) || '';
				var expiredText = t( 'campaign_expired', 'Süresi doldu' );
				var tomorrowText = t( 'campaign_tomorrow', 'Yarın gene bekleriz' );
				var pendingText = timeStart ? ( timeStart + '\'de başlıyor' ) : '';
				var isBar = node.classList.contains( 'ysf-topbar__item' );

				node.classList.toggle( 'is-off', 'hidden' === mode );
				node.classList.toggle( 'is-expired', 'expired' === mode );
				node.classList.toggle( 'is-waiting', 'tomorrow' === mode );
				node.classList.toggle( 'is-pending', 'pending' === mode );

				if ( isBar ) {
					var span = qs( 'span', node );

					if ( span ) {
						if ( 'expired' === mode ) {
							span.textContent = title + ' — ' + expiredText;
						} else if ( 'tomorrow' === mode ) {
							span.textContent = title + ' — ' + tomorrowText;
						} else if ( 'pending' === mode ) {
							span.textContent = title + ( pendingText ? ' — ' + pendingText : '' );
						} else {
							span.textContent = title;
						}
					}
				} else {
					var badge = qs( '[data-ysf-camp-badge]', node );
					var meta = qs( '[data-ysf-camp-meta]', node );
					var liveBadge = node.getAttribute( 'data-badge' ) || '';
					var liveMeta = node.getAttribute( 'data-meta' ) || '';

					if ( badge ) {
						badge.textContent = 'expired' === mode ? expiredText : ( 'tomorrow' === mode ? tomorrowText : ( 'pending' === mode ? pendingText : liveBadge ) );
					}

					if ( meta ) {
						meta.textContent = 'expired' === mode ? expiredText : ( 'tomorrow' === mode ? tomorrowText : ( 'pending' === mode ? pendingText : liveMeta ) );
					}
				}
			} );

			var bars = nodes.filter( function ( node ) {
				return node.classList.contains( 'ysf-topbar__item' );
			} );
			var visibleBars = bars.filter( function ( node ) {
				return ! node.classList.contains( 'is-off' );
			} );

			if ( visibleBars.length && ! visibleBars.some( function ( node ) {
				return node.classList.contains( 'is-active' );
			} ) ) {
				bars.forEach( function ( node ) {
					node.classList.remove( 'is-active' );
				} );
				visibleBars[ 0 ].classList.add( 'is-active' );
			}
		}

		paint();
		window.setInterval( paint, 30000 );
	}

	/* ------------------------------------------------------------------ *
	 * Küçük depolama yardımcıları
	 * ------------------------------------------------------------------ */

	function storeGet( key, fallback, session ) {
		try {
			var raw = ( session ? window.sessionStorage : window.localStorage ).getItem( key );
			return raw ? JSON.parse( raw ) : fallback;
		} catch ( e ) {
			return fallback;
		}
	}

	function storeSet( key, value, session ) {
		try {
			( session ? window.sessionStorage : window.localStorage ).setItem( key, JSON.stringify( value ) );
		} catch ( e ) {
			// Depolama kapalıysa sessizce devam et.
		}
	}

	/* ------------------------------------------------------------------ *
	 * Favoriler
	 * ------------------------------------------------------------------ */

	var FAV_KEY = 'ysf_favs_v1';

	function readFavorites() {
		var list = storeGet( FAV_KEY, [] );

		return Array.isArray( list ) ? list.map( String ) : [];
	}

	function isFavorite( id ) {
		return !! id && readFavorites().indexOf( String( id ) ) > -1;
	}

	function paintFavorite( button ) {
		var on = isFavorite( button.getAttribute( 'data-ysf-fav' ) );
		var card = button.closest( '[data-ysf-item]' );
		var title = card ? qs( '.ysf-item__title', card ) : null;
		var name = title ? ( qs( '.ysf-item__open', title ) || title ).textContent.trim() : '';

		button.classList.toggle( 'is-on', on );
		button.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		button.setAttribute( 'aria-label', ( on ? t( 'fav_remove', '%s favorilerden çıkar' ) : t( 'fav_add', '%s favorilere ekle' ) ).replace( '%s', name ) );
	}

	function initFavorites() {
		var buttons = qsa( '[data-ysf-fav]' );

		buttons.forEach( function ( button ) {
			paintFavorite( button );

			button.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				event.stopPropagation();

				var id = String( button.getAttribute( 'data-ysf-fav' ) );
				var list = readFavorites().filter( function ( entry ) {
					return entry !== id;
				} );

				if ( ! button.classList.contains( 'is-on' ) ) {
					list.unshift( id );
				}

				storeSet( FAV_KEY, list.slice( 0, 200 ) );

				qsa( '[data-ysf-fav="' + id + '"]' ).forEach( paintFavorite );
				document.dispatchEvent( new window.CustomEvent( 'ysf:favorites' ) );
			} );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Ürün detayı + yanında iyi gider
	 * ------------------------------------------------------------------ */

	var PAIR_HINTS = /icecek|içecek|drink|kahve|coffee|tatli|tatlı|dessert|sicak|soguk|sıcak|soğuk|beverage|cay|çay/i;

	function cardInfo( card ) {
		var title = qs( '.ysf-item__title', card );
		var open = title ? qs( '.ysf-item__open', title ) : null;
		var img = qs( '.ysf-item__thumb', card );
		var add = qs( '.ysf-add', card );

		return {
			id: card.getAttribute( 'data-id' ),
			name: open ? open.textContent.trim() : ( title ? title.textContent.trim() : '' ),
			img: card.getAttribute( 'data-full' ) || ( img ? img.currentSrc || img.src : '' ),
			cats: ( card.getAttribute( 'data-cats' ) || '' ).split( ' ' ).filter( Boolean ),
			orderable: !! add && ! card.classList.contains( 'ysf-item--soldout' ),
			price: qs( '.ysf-price', card ),
			card: card
		};
	}

	function pairsFor( info ) {
		var pool = qsa( '[data-ysf-item]' ).map( cardInfo ).filter( function ( other ) {
			return other.id !== info.id && other.orderable && ! other.cats.some( function ( cat ) {
				return info.cats.indexOf( cat ) > -1;
			} );
		} );

		var hinted = pool.filter( function ( other ) {
			return other.cats.some( function ( cat ) {
				return PAIR_HINTS.test( cat );
			} );
		} );

		var list = ( hinted.length >= 2 ? hinted : pool ).slice();
		var seed = Number( info.id ) || 1;

		list.sort( function ( a, b ) {
			return ( ( Number( a.id ) * 7919 + seed ) % 101 ) - ( ( Number( b.id ) * 7919 + seed ) % 101 );
		} );

		var seen = {};

		return list.filter( function ( other ) {
			var key = other.cats[ 0 ] || other.id;

			if ( seen[ key ] ) {
				return false;
			}

			seen[ key ] = true;
			return true;
		} ).slice( 0, 3 );
	}

	function initProductDetail() {
		var dialog = qs( '[data-ysf-detail-dialog]' );

		if ( ! dialog || 'function' !== typeof dialog.showModal ) {
			return;
		}

		var media = qs( '[data-ysf-detail-media]', dialog );
		var titleEl = qs( '[data-ysf-detail-title]', dialog );
		var tagsEl = qs( '[data-ysf-detail-tags]', dialog );
		var descEl = qs( '[data-ysf-detail-desc]', dialog );
		var metaEl = qs( '[data-ysf-detail-meta]', dialog );
		var buyEl = qs( '[data-ysf-detail-buy]', dialog );
		var pairsBox = qs( '[data-ysf-detail-pairs]', dialog );
		var pairList = qs( '[data-ysf-detail-pair-list]', dialog );

		function fill( card ) {
			var info = cardInfo( card );
			var desc = qs( '.ysf-item__desc', card );
			var meta = qs( '.ysf-card__meta', card );
			var side = qs( '.ysf-item__side', card );

			media.textContent = '';

			if ( info.img ) {
				var img = document.createElement( 'img' );
				img.src = info.img;
				img.alt = info.name;
				img.decoding = 'async';
				media.appendChild( img );
			}

			media.hidden = ! info.img;
			titleEl.textContent = info.name;
			tagsEl.textContent = '';

			qsa( '.ysf-item__title .ysf-tag', card ).forEach( function ( tag ) {
				tagsEl.appendChild( tag.cloneNode( true ) );
			} );

			descEl.textContent = desc ? desc.textContent.trim() : '';
			descEl.hidden = ! descEl.textContent;
			metaEl.innerHTML = meta ? meta.innerHTML : '';
			metaEl.hidden = ! meta;

			// Satın alma alanı: kartın kendi düğmeleri taşınmaz, kopyası yerine karta yönlendirilir.
			buyEl.textContent = '';

			if ( side ) {
				var price = qs( '.ysf-price, .ysf-sizes', side );

				if ( price ) {
					buyEl.appendChild( price.cloneNode( true ) );
				}
			}

			var cardSelect = qs( '[data-ysf-size]', card );
			var cardAdd = qs( '.ysf-add', card );

			if ( cardAdd && info.orderable ) {
				var select = null;

				if ( cardSelect ) {
					select = cardSelect.cloneNode( true );
					select.removeAttribute( 'id' );
					select.value = cardSelect.value;
					select.setAttribute( 'aria-label', t( 'size_pick', 'Boy seçin' ) );
					buyEl.appendChild( select );
				}

				var add = document.createElement( 'button' );
				add.type = 'button';
				add.className = 'ysf-btn';
				add.textContent = cardAdd.textContent.trim() || t( 'add_to_cart', 'Sepete ekle' );
				add.addEventListener( 'click', function () {
					if ( select && cardSelect ) {
						cardSelect.value = select.value;
					}

					dialog.close();
					cardAdd.click();
				} );
				buyEl.appendChild( add );
			}

			pairList.textContent = '';
			var pairs = pairsFor( info );

			pairs.forEach( function ( pair ) {
				var li = document.createElement( 'li' );
				var open = document.createElement( 'button' );
				open.type = 'button';
				open.className = 'ysf-detail__pair';

				var thumb = qs( '.ysf-item__thumb', pair.card );

				if ( thumb ) {
					var pic = document.createElement( 'img' );
					pic.src = thumb.currentSrc || thumb.src;
					pic.alt = '';
					pic.loading = 'lazy';
					open.appendChild( pic );
				}

				var label = document.createElement( 'span' );
				label.textContent = pair.name;
				open.appendChild( label );

				if ( pair.price ) {
					var cost = document.createElement( 'small' );
					cost.textContent = pair.price.textContent.replace( /\s+/g, ' ' ).trim();
					open.appendChild( cost );
				}

				open.addEventListener( 'click', function () {
					fill( pair.card );
					dialog.scrollTop = 0;
				} );

				li.appendChild( open );
				pairList.appendChild( li );
			} );

			pairsBox.hidden = ! pairs.length;
		}

		document.addEventListener( 'click', function ( event ) {
			var trigger = event.target.closest( '[data-ysf-detail], .ysf-item__media' );

			if ( ! trigger ) {
				return;
			}

			var card = trigger.closest( '[data-ysf-item]' );

			if ( ! card ) {
				return;
			}

			event.preventDefault();
			fill( card );
			dialog.showModal();
		} );

		dialog.addEventListener( 'click', function ( event ) {
			if ( event.target === dialog ) {
				dialog.close();
			}
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Masa servisi: garson çağır / hesap iste
	 * ------------------------------------------------------------------ */

	var TABLE_KEY = 'ysf_table_v1';

	function currentTable() {
		var params = new window.URLSearchParams( window.location.search );
		var fromUrl = params.get( 'masa' );
		var saved = storeGet( TABLE_KEY, null, true );

		if ( fromUrl ) {
			storeSet( TABLE_KEY, { table: fromUrl, at: Date.now() }, true );
			return fromUrl;
		}

		if ( saved && saved.table && Date.now() - saved.at < 4 * 3600 * 1000 ) {
			return String( saved.table );
		}

		return '';
	}

	function initTableBar() {
		var bar = qs( '[data-ysf-tablebar]' );

		if ( ! bar ) {
			return;
		}

		var select = qs( '[data-ysf-tablebar-select]', bar );
		var msg = qs( '[data-ysf-tablebar-msg]', bar );
		var tables = [];

		try {
			tables = JSON.parse( bar.getAttribute( 'data-tables' ) || '[]' ).map( String );
		} catch ( e ) {
			tables = [];
		}

		var seated = currentTable();

		if ( seated && tables.indexOf( seated ) > -1 ) {
			select.value = seated;
		}

		bar.hidden = false;
		document.body.classList.add( 'has-tablebar' );

		select.addEventListener( 'change', function () {
			if ( select.value ) {
				storeSet( TABLE_KEY, { table: select.value, at: Date.now() }, true );
			}
		} );

		qsa( '[data-ysf-call]', bar ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var type = button.getAttribute( 'data-ysf-call' );

				if ( ! select.value ) {
					msg.textContent = t( 'call_pick_first', 'Önce masa numaranızı seçin.' );
					select.focus();
					return;
				}

				button.disabled = true;
				msg.textContent = t( 'form_sending', 'Gönderiliyor…' );

				request( 'ysf_table_call', { table: select.value, type: type } ).then( function ( response ) {
					msg.textContent = response.payload.message || ( response.ok ? '' : t( 'form_error', 'Bir sorun oluştu.' ) );
				} ).catch( function () {
					msg.textContent = t( 'form_error', 'Bir sorun oluştu.' );
				} ).then( function () {
					window.setTimeout( function () {
						button.disabled = false;
					}, 20000 );
				} );
			} );
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Sipariş takibi
	 * ------------------------------------------------------------------ */

	var ORDERS_KEY = 'ysf_orders_v1';

	function rememberOrder( id, url ) {
		var list = storeGet( ORDERS_KEY, [] );

		list = ( Array.isArray( list ) ? list : [] ).filter( function ( entry ) {
			return entry && String( entry.id ) !== String( id );
		} );

		list.unshift( { id: id, url: url, at: Date.now(), done: false } );
		storeSet( ORDERS_KEY, list.slice( 0, 5 ) );
	}

	function markOrderDone( id ) {
		var list = storeGet( ORDERS_KEY, [] );

		if ( ! Array.isArray( list ) ) {
			return;
		}

		storeSet( ORDERS_KEY, list.map( function ( entry ) {
			if ( entry && String( entry.id ) === String( id ) ) {
				entry.done = true;
			}

			return entry;
		} ) );
	}

	function appendTrackLink( box, url ) {
		if ( ! box ) {
			return;
		}

		var link = document.createElement( 'a' );
		link.className = 'ysf-btn ysf-btn--sm';
		link.href = url;
		link.textContent = t( 'trk_follow', 'Siparişini takip et' );

		var wrap = document.createElement( 'p' );
		wrap.className = 'ysf-alert__hint';
		wrap.appendChild( link );
		box.appendChild( wrap );
	}

	function initTrackChip() {
		var chip = qs( '[data-ysf-track-chip]' );

		if ( ! chip || qs( '[data-ysf-track]' ) ) {
			return;
		}

		var list = storeGet( ORDERS_KEY, [] );
		var live = ( Array.isArray( list ) ? list : [] ).filter( function ( entry ) {
			return entry && ! entry.done && entry.url && Date.now() - entry.at < 3 * 3600 * 1000;
		} );

		if ( ! live.length ) {
			return;
		}

		chip.href = live[ 0 ].url;
		chip.hidden = false;
	}

	function beepTone() {
		try {
			var Ctx = window.AudioContext || window.webkitAudioContext;
			var ctx = new Ctx();
			var osc = ctx.createOscillator();
			var gain = ctx.createGain();

			osc.type = 'sine';
			osc.frequency.value = 880;
			gain.gain.setValueAtTime( 0.0001, ctx.currentTime );
			gain.gain.exponentialRampToValueAtTime( 0.3, ctx.currentTime + 0.02 );
			gain.gain.exponentialRampToValueAtTime( 0.0001, ctx.currentTime + 0.9 );
			osc.connect( gain );
			gain.connect( ctx.destination );
			osc.start();
			osc.stop( ctx.currentTime + 0.95 );
		} catch ( e ) {
			// Ses desteklenmiyorsa sessiz geç.
		}
	}

	function addLinesToCart( lines ) {
		if ( ! Array.isArray( lines ) || ! lines.length ) {
			return;
		}

		var cart = readCart();

		lines.forEach( function ( line ) {
			var key = lineKey( line );
			var found = false;

			cart.forEach( function ( existing ) {
				if ( lineKey( existing ) === key ) {
					existing.qty = Math.min( 50, Number( existing.qty ) + Number( line.qty ) );
					found = true;
				}
			} );

			if ( ! found ) {
				cart.push( {
					id: Number( line.id ),
					name: line.name,
					price: Number( line.price ),
					qty: Math.max( 1, Math.min( 50, Number( line.qty ) || 1 ) ),
					size: line.size || ''
				} );
			}
		} );

		writeCart( cart );
		syncPrices();
		openCart();
	}

	function initReorder() {
		document.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-ysf-reorder]' );

			if ( ! button ) {
				return;
			}

			var raw = button.getAttribute( 'data-ysf-reorder' );
			var lines = [];

			try {
				lines = raw ? JSON.parse( raw ) : ( button.ysfLines || [] );
			} catch ( e ) {
				lines = [];
			}

			if ( ! lines.length && button.ysfLines ) {
				lines = button.ysfLines;
			}

			event.preventDefault();
			addLinesToCart( lines );
		} );
	}

	function initTracking() {
		var root = qs( '[data-ysf-track]' );

		if ( ! root ) {
			return;
		}

		var id = root.getAttribute( 'data-id' );
		var key = root.getAttribute( 'data-k' );
		var stepsEl = qs( '[data-ysf-track-steps]', root );
		var headline = qs( '[data-ysf-track-headline]', root );
		var cancelled = qs( '[data-ysf-track-cancelled]', root );
		var notifyBtn = qs( '[data-ysf-track-notify]', root );
		var linesEl = qs( '[data-ysf-track-lines]', root );
		var totalEl = qs( '[data-ysf-track-total]', root );
		var rateBox = qs( '[data-ysf-track-rate]', root );
		var noteBox = qs( '[data-ysf-track-note]', root );
		var rateResult = qs( '[data-ysf-result]', rateBox );
		var again = qs( '[data-ysf-track-again]', root );
		var againBtn = again ? qs( '[data-ysf-reorder]', again ) : null;
		var state = null;
		var lastStep = null;
		var timer = null;
		var stars = 0;

		try {
			state = JSON.parse( root.getAttribute( 'data-state' ) || 'null' );
		} catch ( e ) {
			state = null;
		}

		function notifyReady( label ) {
			beepTone();

			if ( navigator.vibrate ) {
				navigator.vibrate( [ 200, 100, 200 ] );
			}

			if ( 'Notification' in window && 'granted' === window.Notification.permission ) {
				try {
					new window.Notification( document.title, { body: label, tag: 'ysf-order-' + id } );
				} catch ( e ) {
					// Bazı tarayıcılar yalnızca servis işçisi üzerinden bildirim gösterir.
				}
			}
		}

		function render( data ) {
			if ( ! data ) {
				return;
			}

			state = data;
			stepsEl.textContent = '';

			( data.labels || [] ).forEach( function ( label, index ) {
				var li = document.createElement( 'li' );
				li.textContent = label;

				if ( ! data.cancelled ) {
					if ( index < data.step ) {
						li.className = 'is-done';
					} else if ( index === data.step ) {
						li.className = 4 === index ? 'is-done' : 'is-current';
						li.setAttribute( 'aria-current', 'step' );
					}
				}

				stepsEl.appendChild( li );
			} );

			cancelled.hidden = ! data.cancelled;
			stepsEl.hidden = !! data.cancelled;

			if ( ! data.cancelled && data.labels && data.labels[ data.step ] ) {
				headline.textContent = data.labels[ data.step ];
			}

			linesEl.textContent = '';

			( data.lines || [] ).forEach( function ( line ) {
				var li = document.createElement( 'li' );
				li.textContent = line;
				linesEl.appendChild( li );
			} );

			totalEl.textContent = data.total || '';

			if ( null !== lastStep && data.step !== lastStep && 3 === data.step ) {
				notifyReady( data.labels[ 3 ] );
			}

			lastStep = data.step;

			if ( notifyBtn ) {
				notifyBtn.hidden = ! ( 'Notification' in window ) || 'default' !== window.Notification.permission || data.step >= 3 || data.cancelled;
			}

			rateBox.hidden = ! data.canRate && ! rateBox.getAttribute( 'data-open' );

			if ( again && againBtn ) {
				againBtn.ysfLines = data.reorder || [];
				againBtn.removeAttribute( 'data-ysf-reorder' );
				againBtn.setAttribute( 'data-ysf-reorder', '' );
				again.hidden = ! ( data.reorder && data.reorder.length ) || data.step < 4;
			}

			if ( data.step >= 4 || data.cancelled ) {
				markOrderDone( id );
				window.clearInterval( timer );
			}
		}

		function poll() {
			if ( document.hidden ) {
				return;
			}

			request( 'ysf_order_status', { id: id, k: key } ).then( function ( response ) {
				if ( response.ok ) {
					render( response.payload );
				}
			} ).catch( function () {} );
		}

		render( state );

		if ( ! state || ( state.step < 4 && ! state.cancelled ) ) {
			timer = window.setInterval( poll, 15000 );
			document.addEventListener( 'visibilitychange', poll );
		}

		if ( notifyBtn ) {
			notifyBtn.addEventListener( 'click', function () {
				window.Notification.requestPermission().then( function () {
					notifyBtn.hidden = true;
				} );
			} );
		}

		function sendRating( note ) {
			var data = { id: id, k: key, stars: stars };

			if ( note ) {
				data.note = note;
			}

			return request( 'ysf_order_rate', data ).then( function ( response ) {
				showResult( rateResult, response.payload.message || t( 'form_error', 'Bir sorun oluştu.' ), response.ok );

				if ( ! response.ok ) {
					return;
				}

				rateBox.setAttribute( 'data-open', '1' );
				qsa( '[data-ysf-star]', rateBox ).forEach( function ( star ) {
					star.disabled = true;
				} );

				if ( response.payload.reviewUrl ) {
					var link = document.createElement( 'a' );
					link.className = 'ysf-btn ysf-btn--sm';
					link.href = response.payload.reviewUrl;
					link.target = '_blank';
					link.rel = 'noopener noreferrer';
					link.textContent = t( 'trk_google', 'Google’da yorum yaz' );
					var p = document.createElement( 'p' );
					p.className = 'ysf-alert__hint';
					p.appendChild( link );
					rateResult.appendChild( p );
				}

				noteBox.hidden = ! response.payload.askNote;

				if ( note ) {
					noteBox.hidden = true;
				}
			} );
		}

		qsa( '[data-ysf-star]', rateBox ).forEach( function ( star ) {
			star.addEventListener( 'click', function () {
				stars = Number( star.getAttribute( 'data-ysf-star' ) );

				qsa( '[data-ysf-star]', rateBox ).forEach( function ( other ) {
					var on = Number( other.getAttribute( 'data-ysf-star' ) ) <= stars;
					other.classList.toggle( 'is-on', on );
					other.setAttribute( 'aria-checked', Number( other.getAttribute( 'data-ysf-star' ) ) === stars ? 'true' : 'false' );
				} );

				sendRating( '' );
			} );
		} );

		var send = qs( '[data-ysf-track-send]', rateBox );

		if ( send ) {
			send.addEventListener( 'click', function () {
				var field = qs( 'textarea', noteBox );
				var text = field ? field.value.trim() : '';

				if ( text ) {
					send.disabled = true;
					sendRating( text ).then( function () {
						send.disabled = false;
					} );
				}
			} );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Rezervasyon: müsait saatler ve takvime ekle
	 * ------------------------------------------------------------------ */

	function initReservationSlots() {
		var form = qs( '[data-ysf-form="reservation"]' );
		var date = form ? qs( '[name="date"]', form ) : null;
		var time = form ? qs( '[name="time"]', form ) : null;

		if ( ! date || ! time ) {
			return;
		}

		var hint = document.createElement( 'small' );
		hint.setAttribute( 'aria-live', 'polite' );
		time.parentNode.appendChild( hint );

		function refresh() {
			if ( ! /^\d{4}-\d{2}-\d{2}$/.test( date.value ) ) {
				return;
			}

			request( 'ysf_reservation_slots', { date: date.value } ).then( function ( response ) {
				if ( ! response.ok ) {
					return;
				}

				var full = response.payload.full || [];
				var past = response.payload.past || [];
				var firstFree = null;

				qsa( 'option', time ).forEach( function ( option ) {
					var isFull = full.indexOf( option.value ) > -1;
					var isPast = past.indexOf( option.value ) > -1;

					option.disabled = !! response.payload.closed || isFull || isPast;
					option.textContent = option.value + ( isFull ? ' — ' + t( 'res_slot_full_short', 'dolu' ) : '' );

					if ( ! option.disabled && null === firstFree ) {
						firstFree = option;
					}
				} );

				if ( time.selectedOptions[ 0 ] && time.selectedOptions[ 0 ].disabled && firstFree ) {
					time.value = firstFree.value;
				}

				if ( response.payload.closed ) {
					hint.textContent = t( 'res_day_closed', 'Bu gün kapalıyız, başka bir gün seçin.' );
				} else if ( ! firstFree ) {
					hint.textContent = t( 'res_no_slots', 'Bu gün için boş saat kalmadı.' );
				} else {
					hint.textContent = '';
				}
			} ).catch( function () {} );
		}

		date.addEventListener( 'change', refresh );
		refresh();
	}

	function icsEscape( value ) {
		return String( value || '' ).replace( /\\/g, '\\\\' ).replace( /\n/g, '\\n' ).replace( /([,;])/g, '\\$1' );
	}

	function appendCalendarButton( box, cal ) {
		if ( ! box || ! cal || ! cal.start ) {
			return;
		}

		var tz = cal.tz && cal.tz.indexOf( '/' ) > -1 ? ';TZID=' + cal.tz : '';
		var stamp = new Date().toISOString().replace( /[-:]/g, '' ).replace( /\.\d+/, '' );
		var ics = [
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//YSF Food Lab//Rezervasyon//TR',
			'BEGIN:VEVENT',
			'UID:' + cal.start + '-' + Math.random().toString( 36 ).slice( 2 ) + '@ysffoodlab',
			'DTSTAMP:' + stamp,
			'DTSTART' + tz + ':' + cal.start,
			'DTEND' + tz + ':' + cal.end,
			'SUMMARY:' + icsEscape( cal.title ),
			'LOCATION:' + icsEscape( cal.location ),
			'DESCRIPTION:' + icsEscape( cal.details ),
			'BEGIN:VALARM',
			'TRIGGER:-PT2H',
			'ACTION:DISPLAY',
			'DESCRIPTION:' + icsEscape( cal.title ),
			'END:VALARM',
			'END:VEVENT',
			'END:VCALENDAR'
		].join( '\r\n' );

		var link = document.createElement( 'a' );
		link.className = 'ysf-btn ysf-btn--ghost ysf-btn--sm';
		link.href = URL.createObjectURL( new Blob( [ ics ], { type: 'text/calendar;charset=utf-8' } ) );
		link.download = 'ysf-rezervasyon.ics';
		link.textContent = t( 'res_add_calendar', 'Takvime ekle' );

		var wrap = document.createElement( 'p' );
		wrap.className = 'ysf-alert__hint';
		wrap.appendChild( link );
		box.appendChild( wrap );
	}

	/* ------------------------------------------------------------------ *
	 * Karanlık mod
	 * ------------------------------------------------------------------ */

	function initThemeToggle() {
		var button = qs( '[data-ysf-theme-toggle]' );

		if ( ! button ) {
			return;
		}

		function paint() {
			var dark = 'dark' === document.documentElement.getAttribute( 'data-theme' );
			button.setAttribute( 'aria-pressed', dark ? 'true' : 'false' );
			button.setAttribute( 'aria-label', dark ? t( 'theme_light', 'Açık temaya geç' ) : t( 'theme_dark', 'Karanlık temaya geç' ) );
		}

		button.addEventListener( 'click', function () {
			var next = 'dark' === document.documentElement.getAttribute( 'data-theme' ) ? 'light' : 'dark';

			document.documentElement.setAttribute( 'data-theme', next );

			try {
				window.localStorage.setItem( 'ysf_theme', next );
			} catch ( e ) {
				// Depolama kapalıysa yalnızca bu sayfada geçerli olur.
			}

			paint();
		} );

		paint();
	}

	/* ------------------------------------------------------------------ *
	 * PWA
	 * ------------------------------------------------------------------ */

	function initPwa() {
		if ( ! settings.pwa || ! settings.pwa.sw || ! ( 'serviceWorker' in navigator ) ) {
			return;
		}

		window.addEventListener( 'load', function () {
			navigator.serviceWorker.register( settings.pwa.sw, { scope: settings.pwa.scope || '/' } ).catch( function () {} );
		} );
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
		initAccountCampaigns();
		initAccountAnnouncements();
		initAccountTabs();
		initSavedAddressPicker();
		initMenuFilters();
		initHeroSlider();
		initReveal();
		initCampaignNotices();
		initFavorites();
		initProductDetail();
		initTableBar();
		initReorder();
		initTracking();
		initTrackChip();
		initReservationSlots();
		initThemeToggle();
		initPwa();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
