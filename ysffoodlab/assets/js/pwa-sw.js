/* global YSF */
/**
 * YSF Food Lab müşteri servis işçisi.
 * Başına sunucu tarafından `var YSF={...}` yapılandırması eklenir.
 */
( function () {
	'use strict';

	var CACHE = 'ysf-pwa-' + YSF.version;
	var STATIC_RE = /\.(?:css|js|png|jpe?g|webp|avif|gif|svg|woff2?|ttf)$/i;
	var SKIP_RE = /\/wp-admin|\/wp-login|admin-ajax\.php|\/wp-json|xmlrpc\.php|wp-cron\.php/;
	var SKIP_QS = /(?:^|[?&])(?:ysf_pwa|ysf_sw|ysf_manifest|ysf_siparis|preview|customize_changeset_uuid|s)=/;

	function trimSlash( path ) {
		return path.length > 1 ? path.replace( /\/+$/, '' ) : '';
	}

	function isCacheablePage( url ) {
		return YSF.pages.indexOf( trimSlash( url.pathname ) ) !== -1 && ! SKIP_QS.test( url.search );
	}

	function isStatic( url ) {
		if ( ! STATIC_RE.test( url.pathname ) ) {
			return false;
		}

		return url.pathname.indexOf( YSF.assets ) === 0 ||
			( YSF.uploads && url.pathname.indexOf( YSF.uploads ) === 0 ) ||
			url.pathname.indexOf( '/wp-includes/' ) !== -1;
	}

	function offlineResponse() {
		var o = YSF.offline;
		var html = '<!doctype html><html lang="' + o.lang + '"><head><meta charset="utf-8">' +
			'<meta name="viewport" content="width=device-width,initial-scale=1"><title>' + o.title + '</title>' +
			'<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#14100d;color:#f4ebdd;font:16px/1.5 system-ui,sans-serif;text-align:center;padding:24px}' +
			'h1{font-size:1.4rem;margin:0 0 .5rem}button{margin-top:1rem;padding:.7rem 1.4rem;border:0;border-radius:999px;background:#c8a46a;color:#14100d;font-weight:700;font-size:1rem}</style>' +
			'</head><body><main><h1>' + o.title + '</h1><p>' + o.text + '</p>' +
			'<button onclick="location.reload()">' + o.retry + '</button></main></body></html>';

		return new Response( html, { headers: { 'Content-Type': 'text/html; charset=utf-8' } } );
	}

	self.addEventListener( 'install', function () {
		self.skipWaiting();
	} );

	self.addEventListener( 'activate', function ( event ) {
		event.waitUntil(
			caches.keys().then( function ( keys ) {
				return Promise.all( keys.filter( function ( key ) {
					return key.indexOf( 'ysf-pwa-' ) === 0 && key !== CACHE;
				} ).map( function ( key ) {
					return caches.delete( key );
				} ) );
			} ).then( function () {
				return self.clients.claim();
			} )
		);
	} );

	self.addEventListener( 'fetch', function ( event ) {
		var request = event.request;

		if ( 'GET' !== request.method ) {
			return;
		}

		var url = new URL( request.url );

		if ( url.origin !== self.location.origin || SKIP_RE.test( url.pathname ) ) {
			return;
		}

		if ( 'navigate' === request.mode ) {
			event.respondWith(
				fetch( request ).then( function ( response ) {
					if ( response.ok && isCacheablePage( url ) ) {
						var copy = response.clone();
						caches.open( CACHE ).then( function ( cache ) {
							cache.put( request, copy );
						} );
					}

					return response;
				} ).catch( function () {
					return caches.match( request ).then( function ( hit ) {
						return hit || offlineResponse();
					} );
				} )
			);
			return;
		}

		if ( ! isStatic( url ) ) {
			return;
		}

		event.respondWith(
			caches.open( CACHE ).then( function ( cache ) {
				return cache.match( request ).then( function ( hit ) {
					var network = fetch( request ).then( function ( response ) {
						if ( response.ok ) {
							cache.put( request, response.clone() );
						}

						return response;
					} ).catch( function () {
						return hit;
					} );

					return hit || network;
				} );
			} )
		);
	} );
}() );
