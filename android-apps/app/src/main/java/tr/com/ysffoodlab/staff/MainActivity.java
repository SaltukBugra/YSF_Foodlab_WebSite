package tr.com.ysffoodlab.staff;

import android.annotation.SuppressLint;
import android.app.Activity;
import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.graphics.Color;
import android.net.Uri;
import android.os.Bundle;
import android.text.TextUtils;
import android.view.View;
import android.view.WindowManager;
import android.webkit.CookieManager;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.ProgressBar;

public class MainActivity extends Activity {

	private WebView webView;

	@SuppressLint("SetJavaScriptEnabled")
	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);

		if (BuildConfig.KEEP_SCREEN_ON) {
			getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);
		}

		getWindow().setStatusBarColor(Color.parseColor("#14100d"));
		setContentView(R.layout.activity_main);

		webView = findViewById(R.id.webview);
		ProgressBar progress = findViewById(R.id.progress);

		CookieManager cookies = CookieManager.getInstance();
		cookies.setAcceptCookie(true);
		cookies.setAcceptThirdPartyCookies(webView, false);

		WebSettings settings = webView.getSettings();
		settings.setJavaScriptEnabled(true);
		settings.setAllowFileAccess(false);
		settings.setAllowContentAccess(false);
		settings.setDomStorageEnabled(true);
		settings.setDatabaseEnabled(true);
		settings.setLoadWithOverviewMode(true);
		settings.setUseWideViewPort(true);
		settings.setCacheMode(WebSettings.LOAD_DEFAULT);
		settings.setUserAgentString(settings.getUserAgentString() + " YSFStaffApp/1.4");

		webView.setWebChromeClient(
			new WebChromeClient() {
				@Override
				public void onProgressChanged(WebView view, int newProgress) {
					progress.setVisibility(newProgress >= 100 ? View.GONE : View.VISIBLE);
					progress.setProgress(newProgress);
				}
			}
		);

		webView.setWebViewClient(
			new WebViewClient() {
				@Override
				public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
					Uri uri = request.getUrl();
					String scheme = uri.getScheme() == null ? "" : uri.getScheme().toLowerCase();

					if ("https".equals(scheme) && isAllowedHost(uri.getHost())) {
						return false;
					}

					if ("http".equals(scheme) || "https".equals(scheme)) {
						openExternal(new Intent(Intent.ACTION_VIEW, uri));
						return true;
					}

					if ("intent".equals(scheme)) {
						try {
							Intent intent = Intent.parseUri(uri.toString(), Intent.URI_INTENT_SCHEME);
							intent.addCategory(Intent.CATEGORY_BROWSABLE);
							intent.setComponent(null);
							intent.setSelector(null);
							openExternal(intent);
						} catch (java.net.URISyntaxException ignored) {
						}
						return true;
					}

					if ("tel".equals(scheme) || "mailto".equals(scheme) || "whatsapp".equals(scheme)) {
						openExternal(new Intent(Intent.ACTION_VIEW, uri));
						return true;
					}

					return true;
				}

				@Override
				public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
					if (request.isForMainFrame()) {
						view.loadDataWithBaseURL(
							BuildConfig.START_URL,
							"<html><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"></head>"
								+ "<body style=\"font-family:sans-serif;background:#14100d;color:#fbf7f1;padding:32px\">"
								+ "<h2>Bağlantı yok</h2><p>YSF personel ekranı açılamadı. İnterneti kontrol edip tekrar deneyin.</p>"
								+ "<p><a href=\"" + BuildConfig.START_URL + "\" style=\"display:inline-block;margin-top:16px;padding:12px 24px;"
								+ "background:#fbf7f1;color:#14100d;border-radius:8px;text-decoration:none;font-weight:bold\">Tekrar dene</a></p>"
								+ "</body></html>",
							"text/html",
							"utf-8",
							null
						);
					}
				}
			}
		);

		if (savedInstanceState == null) {
			webView.loadUrl(BuildConfig.START_URL);
		} else {
			webView.restoreState(savedInstanceState);
		}
	}

	private static boolean isAllowedHost(String host) {
		if (TextUtils.isEmpty(host)) {
			return false;
		}
		String startHost = Uri.parse(BuildConfig.START_URL).getHost();
		if (startHost == null) {
			return false;
		}
		String bare = stripWww(startHost.toLowerCase());
		return bare.equals(stripWww(host.toLowerCase()));
	}

	private static String stripWww(String host) {
		return host.startsWith("www.") ? host.substring(4) : host;
	}

	private void openExternal(Intent intent) {
		try {
			startActivity(intent);
		} catch (ActivityNotFoundException ignored) {
		}
	}

	@Override
	public void onBackPressed() {
		if (webView != null && webView.canGoBack()) {
			webView.goBack();
			return;
		}
		super.onBackPressed();
	}

	@Override
	protected void onSaveInstanceState(Bundle outState) {
		super.onSaveInstanceState(outState);
		if (webView != null) {
			webView.saveState(outState);
		}
	}

	@Override
	protected void onPause() {
		super.onPause();
		CookieManager.getInstance().flush();
	}
}
