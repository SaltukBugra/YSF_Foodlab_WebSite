package tr.com.ysffoodlab.staff;

import android.annotation.SuppressLint;
import android.app.Activity;
import android.graphics.Color;
import android.os.Bundle;
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
		cookies.setAcceptThirdPartyCookies(webView, true);

		WebSettings settings = webView.getSettings();
		settings.setJavaScriptEnabled(true);
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
					return false;
				}

				@Override
				public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
					if (request.isForMainFrame()) {
						view.loadDataWithBaseURL(
							BuildConfig.START_URL,
							"<html><body style=\"font-family:sans-serif;background:#14100d;color:#fbf7f1;padding:32px\">"
								+ "<h2>Baglanti yok</h2><p>YSF personel ekrani acilamadi. Interneti kontrol edip tekrar deneyin.</p>"
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
