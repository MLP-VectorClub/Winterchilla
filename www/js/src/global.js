/* jshint bitwise: false */
/* global $w,$d,$head,$navbar,$body,$header,$sidebar,$sbToggle,$main,$footer,console,prompt,HandleNav,getTimeDiff,one,createTimeStr,PRINTABLE_ASCII_PATTERN,io,moment,Time,ace,mk,WSNotifications */
(function($){
	"use strict";

	let fluidboxThisAction = (jQueryObject) => {
		jQueryObject.fluidbox({
			immediateOpen: true,
			loader: true,
		})
		.on('openstart.fluidbox',function(){
			$body.addClass('fluidbox-open');
			let $this = $(this);
			if ($this.parents('#dialogContent').length)
				$body.addClass('fluidbox-in-dialog');
		})
		.on('openend.fluidbox',function(){
			let $this = $(this),
				href = $this.attr('href');
			$this.data('href', href);
			$this.removeAttr('href');
			let $ghost = $this.find('.fluidbox__ghost');
			if ($ghost.children().length === 0)
				$this.find('.fluidbox__ghost').append(
					$.mk('img').attr('src',href).css({
						opacity: 0,
						width: '100%',
						height: '100%',
					})
				);
		})
		.on('closestart.fluidbox', function() {
			$body.removeClass('fluidbox-open');
			let $this = $(this);
			$this.attr('href', $this.data('href'));
			$this.removeData('href');
		})
		.on('closeend.fluidbox',function(){
			$body.removeClass('fluidbox-in-dialog');
		});
	};
	$.fn.fluidboxThis = function(callback){
		if (typeof $.fn.fluidbox === 'function'){
			fluidboxThisAction(this);
			$.callCallback(callback);
		}
		else {
			$.getScript('/js/min/jquery.ba-throttle-debounce.js',() => {
				$.getScript('/js/min/jquery.fluidbox.js',() => {
					fluidboxThisAction(this);
					$.callCallback(callback);
				}).fail(function(){
					$.Dialog.fail(false, 'Loading Fluidbox plugin failed');
				});
			}).fail(function(){
				$.Dialog.fail(false, 'Loading Debounce/throttle plugin failed');
			});
		}
		return this;
	};

	// http://stackoverflow.com/a/16861050
	let popupCalcCenter = (w, h) => {
		let dualScreenLeft = typeof window.screenLeft !== 'undefined' ? window.screenLeft : screen.left,
			dualScreenTop = typeof window.screenTop !== 'undefined' ? window.screenTop : screen.top,
			width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width,
			height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height,
			left = ((width / 2) - (w / 2)) + dualScreenLeft,
			top = ((height / 2) - (h / 2)) + dualScreenTop;

		return {top:top, left:left};
	};
	$.PopupOpenCenter = (url, title, w, h) => {
		let calcpos = popupCalcCenter(w,h),
			newWindow = window.open(url,title,`scrollbars=yes,width=${w},height=${h},top=${calcpos.top},left=${calcpos.left}`);

		if (window.focus)
			newWindow.focus();

		return newWindow;
	};
	$.PopupMoveCenter = (popup, w, h) => {
		let calcpos = popupCalcCenter(w,h);
		popup.resizeTo(w,h);
		popup.moveTo(calcpos.left,calcpos.top);
	};
	let OAUTH_URL = window.OAUTH_URL,
		signingGenRndKey = () => (~~(Math.random()*99999999)).toString(36);
	$d.on('click','#turbo-sign-in',function(e){
		e.preventDefault();

		let $this = $(this),
			origNotice = $this.parent().html();
		$this.disable();
		OAUTH_URL = $this.attr('data-url');

		let rndk = signingGenRndKey(),
			success = false,
			closeCheck,
			popup;
		window[' '+rndk] = function(){
			success = true;
			if ($.Dialog._open.type === 'request')
				$.Dialog.clearNotice(/Redirecting you to DeviantArt/);
			else $.Dialog.close();
			popup.close();
		};
		try {
			popup = window.open(OAUTH_URL+'&state='+rndk);
		}
		catch(_){ return $.Dialog.fail(false, 'Could not open login pop-up. Please open another page') }

		$.Dialog.wait(false, 'Redirecting you to DeviantArt');
		closeCheck = setInterval(function(){
			try {
				if (!popup || popup.closed){
					clearInterval(closeCheck);
					if (success)
						return;
					$.Dialog.fail(false, origNotice);
				}
			}catch(e){}
		}, 500);
	});

	let docReadyAlwaysRun = function(){
		console.log('> docReadyAlwaysRun()');
		$d.triggerHandler('paginate-refresh');

		// Sign in button handler
		$.LocalStorage.remove('cookie_consent');
		let consent = $.LocalStorage.get('cookie_consent_v2');
		OAUTH_URL = window.OAUTH_URL;

		$('#signin').off('click').on('click',function(){
			let $this = $(this),
				opener = function(sure){
					if (!sure) return;

					$.Dialog.close();
					$.LocalStorage.set('cookie_consent_v2',1);
					$this.disable();

					let redirect = function(){
						$.Dialog.wait(false, 'Redirecting you to DeviantArt');
						location.href = OAUTH_URL+"&state="+encodeURIComponent(location.href.replace(location.origin,''));
					};

					if (navigator.userAgent.indexOf('Trident') !== -1)
						return redirect();

					$.Dialog.wait('Sign-in process', "Opening popup window");

					let rndk = signingGenRndKey(), success = false, closeCheck, popup, waitforit = false;
					window[' '+rndk] = function(fail, openedWindow){
						clearInterval(closeCheck);
						if (fail === true){
							if (!openedWindow.jQuery)
								$.Dialog.fail(false, 'Sign in failed, check popup for details.');
							else {
								const
									pageTitle = openedWindow.$('#content').children('h1').text(),
									noticeText = openedWindow.$('#content').children('.notice').html();
								$.Dialog.fail(false, `<p class="align-center"><strong>${pageTitle}</strong></p><p>${noticeText}</p>`);
								popup.close();
							}
							$this.enable();
							return;
						}

						success = true;
						$.Dialog.success(false, 'Signed in successfully');
						$.Dialog.wait(false, 'Reloading page');
						waitforit = true;
						setTimeout(function(){
							popup.close();
						},750);
						$.Navigation.reload(function(){
							waitforit = false;
							$.Dialog.close();
						});
					};
					try {
						popup = $.PopupOpenCenter(OAUTH_URL+"&state="+rndk,'login','450','580');
					}catch(e){}
					// http://stackoverflow.com/a/25643792
					let onWindowClosed = function(){
							if (success)
								return;

							if (document.cookie.indexOf('auth=') !== -1)
								return window[' '+rndk];

							$.Dialog.fail(false, 'Popup-based login unsuccessful');
							redirect();
						};
					closeCheck = setInterval(function(){
						try {
							if (!popup || popup.closed){
								clearInterval(closeCheck);
								onWindowClosed();
							}
						}catch(e){}
					}, 500);
					$w.on('beforeunload', function(){
						success = true;
						if (!waitforit)
							popup.close();
					});
					$.Dialog.wait(false, "Waiting for you to sign in");
				};

			if (!consent) $.Dialog.confirm('Privacy Notice',`<p>We must inform you that our website will store cookies on your device to remember your logged in status between browser sessions.</p><p>If you would like to avoid these completly harmless pieces of text which are required to log in to this website, click "Decline" and continue browsing as a guest.</p><p><em>This warning will not appear again if you accept our use of persistent cookies.</em></p>`,['Accept','Decline'],opener);
			else opener(true);
		});

		// Sign out button handler
		$('#signout').off('click').on('click',function(){
			let title = 'Sign out';
			$.Dialog.confirm(title,'Are you sure you want to sign out?', function(sure){
				if (!sure) return;

				$.Dialog.wait(title,'Signing out');

				$.post('/da-auth/signout',$.mkAjaxHandler(function(){
					if (!this.status) return $.Dialog.fail(title,this.message);

					$.Navigation.reload(function(){
						$.Dialog.close();
					});
				}));
			});
		});

		// HTTPS button
		try {
			if (/^https/.test(location.protocol))
				throw undefined;
			let canhttps = $.SessionStorage.get('canhttps');
			if (canhttps === 'false')
				throw undefined;
			$.ajax({
				method: "POST",
				url: 'https://'+location.host+'/ping',
				success: $.mkAjaxHandler(function(){
					if (this.status)
						$sidebar.append(
							$.mk('a').attr({
								'class': 'btn green typcn typcn-lock-closed',
								href: location.href.replace(/^http:/,'https:')
							}).text('Switch to HTTPS')
						);
					$.SessionStorage.set('canhttps', canhttps = this.status.toString());
				}),
				error: function(){ $.SessionStorage.set('canhttps', canhttps = 'false') }
			});
		}
		catch(e){}
	};

	class Loader {
		constructor(o){
			this._options = $.extend({
				min: 0,
				max: 0.98,
				intervalEnabled: true,
				intervalDelay: 800,
				randomIncrement: 0.08,
			},o);
			this._$element = $header.find('.loading-indicator').children('.loading-circle');
			if (this._$element.length !== 1)
				throw new Error('Loader: Element not found');

			this._circumference = this._$element.attr('r')*2*Math.PI;
			this._$element.css('stroke-dasharray', this._circumference);
			this.val(1, 1);
		}
		show(){
			$body.addClass('loading');
			this.val(0);
			this._startInterval();
		}
		_startInterval(){
			if (!this._options.intervalEnabled)
				return;
			if (typeof this._interval !== 'undefined')
				return;
			this._interval = setInterval(() => {
				this.inc(null, 0.5);
			}, this._options.intervalDelay);
		}
		_clearInterval(){
			if (typeof this._interval === 'undefined')
				return false;

			clearInterval(this._interval);
			this._interval = undefined;
		}
		enableInterval(){
			this._options.intervalEnabled = true;
			this._startInterval();
		}
		disableInterval(){
			this._options.intervalEnabled = false;
			this._clearInterval();
		}
		val(p, upto){
			if (!isNaN(p))
				this.at = $.rangeLimit(p, false, this._options.min, upto||this._options.max);
			this._$element.css('stroke-dashoffset', this._circumference*(1-this.at));
		}
		inc(p, upto){
			if (!isNaN(p))
				p = Math.random() * this._options.randomIncrement;

			if (this._options.max < this.at+p)
				return false;
			this.val(this.at+p, upto);
		}
		finish(){
			this.val(1, 1);
			this.hide();
		}
		hide(){
			this._clearInterval();
			$body.removeClass('loading');
		}
	}

	$.Loader = new Loader();

	class Navigation {
		constructor(){
			this._DocReadyHandlers = [];
			this._xhr = false;
			this._lastLoadedPathname = window.location.pathname;
			this._lastLoadedHref = window.location.href;
			this.firstLoadDone = false;
		}
		_docReady(){
			console.groupCollapsed('> _docReady()');
			docReadyAlwaysRun();
			console.groupEnd();

			for (let i = 0, l = this._DocReadyHandlers.length; i<l; i++){
				try {
					console.group('DocReadyHandlers[%d]()',i);
					this._DocReadyHandlers[i].call(window);
					console.groupEnd();
				}
				catch(e){
					console.error(`Error while executing DocReadyHandlers[${i}]\n${e.stack}`);
				}
			}
		}
		flushDocReady(){
			$('.marquee').trigger('destroy.simplemarquee').removeClass('marquee');

			for (let i=0,l=this._DocReadyHandlers.length; i<l; i++){
				if (typeof this._DocReadyHandlers[i].flush !== 'function')
					continue;

				this._DocReadyHandlers[i].flush();
				console.log('Flushed DocReady handler #%d', i);
			}
			this._DocReadyHandlers = [];
		}
		_loadCSS(css, tick, callback){
			if (!css.length)
				return $.callCallback(callback);

			console.groupCollapsed('Loading CSS');
			let classScope = this;
			(function _recursivelyLoadCSS(item){
				if (item >= css.length){
					console.groupEnd();
					return $.callCallback(callback);
				}

				let requrl = css[item];
				classScope = $.ajax({
					url: requrl,
					dataType: 'text',
					success: function(data){
						data = data.replace(/url\((['"])?(?:\.\.\/)+/g,'url($1/');
						$head.append($.mk('style').attr('href',requrl).text(data));
						console.log('%c#%d (%s)', 'color:green', item, requrl);
					},
					error: function(){
						console.log('%c#%d (%s)', 'color:red', item, requrl);
					},
					complete: function(){ tick(); _recursivelyLoadCSS(item+1) }
				});
			})(0);
		}
		_loadJS(js, tick, callback){
			if (!js.length)
				return $.callCallback(callback);

			console.groupCollapsed('Loading JS');
			let classScope = this;
			(function _recursivelyLoadJS(item){
				if (item >= js.length){
					console.groupEnd();
					return $.callCallback(callback);
				}

				let requrl = js[item];
				classScope._xhr = $.ajax({
					url: requrl,
					dataType: 'text',
					success:function(data){
						$body.append(
							$.mk('script').attr('data-src', requrl).text(data)
						);
						console.log('%c#%d (%s)', 'color:green', item, requrl);
					},
					error: function(){
						console.log('%c#%d (%s)', 'color:red', item, requrl);
					},
					complete: function(){ tick(); _recursivelyLoadJS(item+1) }
				});
			})(0);
		}
		visit(url, callback, block_reload){
			console.clear();
			console.group('[AJAX-Nav] PING %s (block_reload: %s)', url, block_reload);

			let classScope = this;
			if (classScope._xhr !== false){
				try {
					classScope._xhr.abort();
					console.log('Previous AJAX request aborted');
				}catch(e){}
				classScope._xhr = false;
			}

			$.Loader.show();
			$.Loader.enableInterval();
			let ajaxcall = $.ajax({
				url: url,
				data: {'via-js': true},
				success: $.mkAjaxHandler(function(){
					if (classScope._xhr !== ajaxcall){
						console.log('%cAJAX request objects do not match, bail','color:red');
						console.groupEnd();
						return;
					}
					if (!this.status){
						$body.removeClass('loading');
						$.Loader.finish();

						classScope._xhr = false;
						console.log('%cNavigation error %s', 'color:red', this.message);
						console.groupEnd();
						return $.Dialog.fail('Navigation error', this.message);
					}

					$.Loader.val(0.5);
					$.Loader.disableInterval();

					url = new URL(this.responseURL).pathString+(new URL(url).hash);
					$w.triggerHandler('unload');
					if (!window.OpenSidebarByDefault())
						$sbToggle.trigger('sb-close');

					let css = this.css,
						js = this.js,
						content = this.content,
						sidebar = this.sidebar,
						footer = this.footer,
						pagetitle = this.title,
						avatar = this.avatar,
						signedIn = this.signedIn;

					$main.empty();
					let doreload = false,
						ParsedLocation = new URL(location.href),
						reload = !block_reload && ParsedLocation.pathString === url;

					classScope.flushDocReady();

					console.groupCollapsed('Checking JS files to skip...');
					$body.children('script[src], script[data-src]').each(function(){
						let $this = $(this),
							src = $this.attr('src') || $this.attr('data-src');
						if (reload){
							if (!/min\/dialog\.js/.test(src))
								$this.remove();
							return true;
						}

						let pos = js.indexOf(src);

						// TODO Come up with a proper way to handle persistent files rather than re-requesting them
						if (pos !== -1 && !/min\/(colorguide[.-]|events-manage|episodes-manage|moment-timezone|episode|Chart|user[.-])/.test(src)){
							js.splice(pos, 1);
							console.log('%cSkipped %s','color:saddlebrown',src);
						}
						else {
							if (src.includes('global'))
								return !(doreload = true);
							$this.remove();
						}
					});
					console.log('%cFinished.','color:green');
					console.groupEnd();
					if (doreload !== false){
						console.log('%cFull page reload forced by changes in global.js', 'font-weight:bold;color:orange');
						console.groupEnd();
						classScope._xhr.abort();
						location.href = url;
						return;
					}

					console.groupCollapsed('Checking CSS files to skip...');
					let CSSSelector = 'link[href], style[href]';
					$head.children(CSSSelector).each(function(){
						let $this = $(this),
							href = $this.attr('href'),
							pos = css.indexOf(href);

						if (pos !== -1){
							css.splice(pos, 1);
							console.log('%cSkipped %s','color:saddlebrown',href);
						}
						else $this.attr('data-remove','true');
					});
					console.log('%cFinished.','color:green');
					console.groupEnd();

					console.groupEnd();
					console.group('[AJAX-Nav] GET %s', url);

					let loaded = 0,
						total = css.length+js.length;
					$w.trigger('beforeunload');
					classScope._loadCSS(css, function(){
						loaded++;
						$.Loader.val(0.5+((loaded/total)*0.5));
					}, function(){
						$head.children(CSSSelector.replace(/href/g,'data-remove=true')).remove();
						$main.addClass('pls-wait').html(content);
						$sidebar.html(sidebar);
						$footer.html(footer);
						Time.Update();
						window.setUpcomingCountdown();
						let $headerNav = $header.find('nav').children();
						$headerNav.children().first().children('img').attr('src', avatar);
						$headerNav.children(':not(:first-child)').remove();
						$headerNav.append($sidebar.find('nav').children().children().clone());

						window.CommonElements();
						if (!block_reload)
							history[ParsedLocation.pathString === url?'replaceState':'pushState']({'via-js':true},'',url);
						document.title = pagetitle;
						classScope._lastLoadedPathname = window.location.pathname;
						classScope._lastLoadedHref = window.location.href;

						classScope._loadJS(js, function(){
							loaded++;
							$.Loader.val(0.5+((loaded/total)*0.5));
						}, function(){
							console.log('> $(document).ready() [simulated]');
							classScope._docReady();
							console.log('%cDocument ready handlers called','color:green');
							console.groupEnd();
							$main.removeClass('pls-wait');

							if (signedIn)
								$.WS.authme();
							else $.WS.unauth();
							$.WS.navigate();

							$.callCallback(callback);
							$.Loader.finish();
							classScope._xhr = false;
						});
					});
				})
			});
			classScope._xhr = ajaxcall;
		}
		reload(callback){
			this.visit(location.pathname+location.search+location.hash, callback);
		}
	}

	$.Navigation = new Navigation();
	window.DocReady = {
		push: (handler, flusher) => {
			if (typeof flusher === 'function')
				handler.flush = flusher;
			$.Navigation._DocReadyHandlers.push(handler);
		}
	};
})(jQuery);
// Runs on first load
$(function(){
	'use strict';

	if ($.Navigation.firstLoadDone)
		return;
	$.Navigation.firstLoadDone = true;

	console.log('[HTTP-Nav] > $(document).ready()');
	console.group('[HTTP-Nav] GET '+window.location.pathname+window.location.search+window.location.hash);

	if ('serviceWorker' in navigator){
		window.addEventListener('load', function(){
			navigator.serviceWorker.register('/sw.js').then(function(registration){
				// Registration was successful
				//console.log('ServiceWorker registration successful with scope: ', registration.scope);
			}).catch(function(err){
				// registration failed :(
				//console.log('ServiceWorker registration failed: ', err);
			});
		});
	}

	// Load footer
	if (window.ServiceUnavailableError !== true)
		$.get('/footer-git',$.mkAjaxHandler(function(){
			if (this.footer)
				$footer.prepend(this.footer);
		}));

	// Sidebar toggle handler
	(function(){
		let triggerResize = function(){
			setTimeout(function(){
				$w.trigger('resize');
			},510);
		};

		$sbToggle.off('click sb-open sb-close').on('click', function(e){
			e.preventDefault();

			$sbToggle.trigger('sb-'+($body.hasClass('sidebar-open')?'close':'open'));
		}).on('sb-open sb-close', function(e){
			let close = e.type.substring(3) === 'close';
			$body[close ? 'removeClass' : 'addClass']('sidebar-open');
			try {
				$.LocalStorage[close ? 'set' : 'remove']('sidebar-closed', 'true');
			}catch(_){}
			triggerResize();
		});

		let sbclosed;
		try {
			sbclosed = $.LocalStorage.get('sidebar-closed');
		}catch(_){}
		if (sbclosed !== 'true' && window.OpenSidebarByDefault()){
			$body.addClass('sidebar-open');
			triggerResize();
		}
	})();

	// Upcoming Countdowns
	(function(){
		let $cd, cdtimer,
			clearCD = function(){
				if (typeof cdtimer !== 'undefined'){
					clearInterval(cdtimer);
					cdtimer = undefined;
				}
			},
			cdupdate = function(){
				let cdExists = typeof $cd.parent === "function" && $cd.parent().length !== 0,
					diff = {}, now, airs;
				if (cdExists){
					now = new Date();
					airs = new Date($cd.attr('datetime'));
					diff = Time.Difference(now, airs);
				}
				if (!cdExists || diff.past){
					if (cdExists){
						$cd.find('.marquee').trigger('destroy.simplemarquee');
						$cd.parents('li').remove();
					}
					clearCD();
					return window.setUpcomingCountdown();
				}
				let text;
				if (diff.time < Time.InSeconds.month && diff.month === 0){
					if (diff.week > 0)
						diff.day += diff.week * 7;
					text = 'in ';
					if (diff.day > 0)
						text += diff.day+' day'+(diff.day!==1?'s':'')+' & ';
					if (diff.hour > 0)
						text += diff.hour+':';
					text += $.pad(diff.minute)+':'+$.pad(diff.second);
				}
				else {
					clearCD();
					setTimeout(cdupdate, 10000);
					text = moment(airs).from(now);
				}
				$cd.text(text);
			};
		window.setUpcomingCountdown = function(){
			let $uc = $('#upcoming');
			if (!$uc.length)
				return;

			let $lis = $uc.children('ul').children();
			if (!$lis.length)
				return $uc.remove();

			$cd = $lis.first().find('time').addClass('nodt');
			clearCD();
			cdtimer = setInterval(cdupdate, 1000);
			cdupdate();

			$uc.find('li').each(function(){
				let $this = $(this),
					$calendar = $this.children('.calendar'),
					d = moment($this.find('.countdown').data('airs') || $this.find('time').attr('datetime'));
				$calendar.children('.top').text(d.format('MMM'));
				$calendar.children('.bottom').text(d.format('D'));
			});
			Time.Update();

			let succ = function(){
				$lis.find('.title').simplemarquee({
				    speed: 25,
				    cycles: Infinity,
				    space: 25,
				    handleHover: false,
				    delayBetweenCycles: 0,
				}).addClass('marquee');
			};
			if (typeof jQuery.fn.simplemarquee !== 'function')
				$.ajax({
					url: '/js/min/jquery.simplemarquee.js',
					dataType: "script",
					cache: true,
					success: succ
				});
			else succ();
		};
		window.setUpcomingCountdown();
	})();

	// Feedback form
	$(document).off('click','.send-feedback').on('click','.send-feedback', function(e){
		e.preventDefault();
		e.stopPropagation();
		$('#ctxmenu').hide();

		$.Dialog.info($.Dialog.isOpen() ? undefined : 'Send feedback',
			`<h3>How to send feedback</h3>
			<p>If you're having an issue with the site and would like to let us know or have an idea/feature request you’d like to share, here’s how:</p>
			<ul>
				<li><a href='https://discord.gg/0vv70fepSILbdJOD'>Join our Discord server</a> and describe your issue in the <strong>#support</strong> channel</li>
				<li><a href='http://mlp-vectorclub.deviantart.com/notes/'>Send a note </a>to the group on DeviantArt</li>
				<li><a href='mailto:seinopsys@gmail.com'>Send an e-mail</a> to seinopsys@gmail.com</li>
				<li>If you have a GitHub account, you can also  <a href="${$footer.find('a.issues').attr('href')}">create an issue</a> on the project’s GitHub page.
			</ul>`
		);
	});

	// Color Average form
	$(document).off('click','.action--color-avg').on('click','.action--color-avg', function(e){
		e.preventDefault();
		e.stopPropagation();

		let title = 'Colour Average Calculator',
			callme = function(){
				$.Dialog.close();
				let $clone = window.$ColorAvgFormTemplate.clone(true,true);
				$.Dialog.request(title,$clone,false, function(){
					$clone.triggerHandler('added');
				});
			};

		if (typeof window.$ColorAvgFormTemplate === 'undefined'){
			$.Dialog.wait(title,'Loading form, please wait');
			let scriptUrl = '/js/min/global-color_avg_form.js';
			$.getScript(scriptUrl,callme).fail(function(){
				setTimeout(function(){
					$.Dialog.close(function(){
						$.Dialog.wait(title, 'Loading script (attempt #2)');
						$.getScript(scriptUrl.replace(/min\./,''), callme).fail(function(){
							$.Dialog.fail(title, 'Form could not be loaded');
						});
					});
				},1);
			});
		}
		else callme();
	});

	// Navigation handling
	$d.on('click','a[href]', function(e){
		if (e.which > 2 || e.ctrlKey || e.shiftKey) return true;

		let link = this;
		if (link.host !== location.host) return true;

		if (link.pathname === location.pathname && link.search === location.search){
			if (link.protocol !== location.protocol)
				return true;
			e.preventDefault();
			window._trighashchange = link.hash !== location.hash;
			if (window._trighashchange === true)
				history.replaceState(history.state,'',link.href);
			setTimeout(function(){ delete window._trighashchange },1);
			$w.triggerHandler('hashchange');
			return;
		}

		// Check if link seems to have a file extension
		if (!/^.*\/[^.]*$/.test(link.pathname))
			return true;

		if ($(this).parents('#dialogContent').length !== 0)
			$.Dialog.close();

		e.preventDefault();
		$.Navigation.visit(this.href);
	});
	$w.on('popstate', function(e){
		let state = e.originalEvent.state,
			goto = (url, callback) => $.Navigation.visit(url, callback, true);

		if (state !== null && !state['via-js'] && state.paginate === true)
			return $w.trigger('nav-popstate', [state, goto]);

		if ($.Navigation._lastLoadedHref.replace(/#.*$/,'') === location.href.replace(/#.*$/,'')){
			console.log('[AJAX-Nav] Hashchange detected, navigation blocked');
			return;
		}
		goto(location.href);
	});

	// Disappearing header when in standalone mode
	// Replace condition with "true" when needed for development
	if ($.isRunningStandalone()){
		let lastScrollTop = $body.scrollTop(),
			disappearingHeaderHandler = function(){
				if (!window.WithinMobileBreakpoint())
					return;

				let scrollTop = $body.scrollTop(),
					headerHeight = $header.outerHeight(),
					headerTop = parseInt($header.css('top'),10);

				$header.css('top',
					scrollTop > lastScrollTop
					 ? Math.max(-headerHeight,headerTop-(scrollTop-lastScrollTop))
					 : Math.min(0,headerTop+(lastScrollTop-scrollTop))
				);

				lastScrollTop = scrollTop;
			};
		$w.on('scroll',disappearingHeaderHandler);
		disappearingHeaderHandler();
	}

	// WebSocket server connection
	(function(){
		let conn,
			connpath = `https://ws.${location.hostname}:8667/`,
			wsdecoder = f =>
				function(data){
					if (typeof data === 'string'){
						try {
							data = JSON.parse(data);
						}
						catch(err){}
					}

					f(data);
				},
			$notifCnt,
			$notifSb,
			$notifSbList,
			auth = false,
			bindMarkRead = function(){
				$notifSbList.off('click','.mark-read').on('click','.mark-read', function(e){
					e.preventDefault();
					e.stopPropagation();

					let $el = $(this);
					if ($el.is(':disabled'))
						return;

					let nid = $el.attr('data-id'),
						data = {read_action: $el.attr('data-value')},
						send = function(){
							$el.css('opacity', '.5').disable();

							$.post(`/notifications/mark-read/${nid}`,data,$.mkAjaxHandler(function(){
								if (this.status)
									return;

								$el.css('opacity', '').enable();
								return $.Dialog.fail('Mark notification as read', this.message);
							}));
						};

					if (data.read_action)
						$.Dialog.confirm('Actionable notification',`Please confirm your choice: <strong class="color-${$el.attr('class').replace(/^.*variant-(\w+)\b.*$/,'$1')}">${$el.attr('title')}</strong>`,['Confirm','Cancel'], sure => {
							if (!sure) return;

							$.Dialog.close();
							send();
						});
					else send();
				});
			},
			essentialElements = function(){
				$notifCnt = $sbToggle.children('.notif-cnt');
				if ($notifCnt.length === 0)
					$notifCnt = $.mk('span').attr({'class':'notif-cnt',title:'New notifications'}).prependTo($sbToggle);
				$notifSb = $sidebar.children('.notifications');
				$notifSbList = $notifSb.children('.notif-list');

				bindMarkRead();
			};
		function wsNotifs(){
			let success = function(){
				essentialElements();

				if (conn)
					return;

				conn = io(connpath, { reconnectionDelay: 10000 });
				conn.on('connect', function(){
					console.log('[WS] %cConnected','color:green');

					$.WS.recvPostUpdates(typeof window.EpisodePage !== 'undefined');
					$.WS.navigate();
				});
				conn.on('auth', wsdecoder(function(data){
					auth = true;
					console.log(`[WS] %cAuthenticated as ${data.name}`,'color:teal');
				}));
				conn.on('auth-guest', wsdecoder(function(){
					console.log(`[WS] %cReceiving events as a guest`,'color:teal');
				}));
				conn.on('notif-cnt', wsdecoder(function(data){
					let cnt = data.cnt ? parseInt(data.cnt, 10) : 0;
					console.log('[WS] Unread notification count: %d', cnt);

					essentialElements();

					if (cnt === 0){
						$notifSb.stop().slideUp('fast',function(){
							$notifSbList.empty();
							$notifCnt.empty();
						});
					}
					else $.post('/notifications/get',$.mkAjaxHandler(function(){
						$notifCnt.text(cnt);
						$notifSbList.html(this.list);
						Time.Update();
						bindMarkRead();
						$notifSb.stop().slideDown();
					}));
				}));
				conn.on('post-delete', wsdecoder(function(data){
					if (!data.type || !data.id)
						return;

					let postid = `${data.type}-${data.id}`,
						$post = $(`#${postid}:not(.deleting)`);
					console.log('[WS] Post deleted (postid=%s)', postid);
					if ($post.length){
						$post.find('.fluidbox--opened').fluidbox('close');
						$post.find('.fluidbox--initialized').fluidbox('destroy');
						$post.attr({
							'class': 'deleted',
							title: "This post has been deleted; click here to hide",
						}).on('click',function(){
							let $this = $(this);
							$this[window.WithinMobileBreakpoint()?'slideUp':'fadeOut'](500,function(){
								$this.remove();
							});
						});
					}
				}));
				conn.on('post-break', wsdecoder(function(data){
					if (!data.type || !data.id)
						return;

					let postid = `${data.type}-${data.id}`,
						$post = $(`#${postid}:not(.admin-break)`);
					console.log('[WS] Post broken (postid=%s)', postid);
					if ($post.length){
						$post.find('.fluidbox--opened').fluidbox('close');
						$post.find('.fluidbox--initialized').fluidbox('destroy');
						$post.reloadLi();
					}
				}));
				conn.on('post-add', wsdecoder(function(data){
					if (!data.type || !data.id || window.EPISODE !== data.episode || window.SEASON !== data.season)
						return;

					if ($(`.posts #${data.type}-${data.id}`).length > 0)
						return;
					$.post(`/post/reload/${data.type}/${data.id}`,$.mkAjaxHandler(function(){
						if (!this.status) return;

						if ($(`.posts #${data.type}-${data.id}`).length > 0)
							return;
						let $newli = $(this.li);
						$(this.section).append($newli);
						$newli.rebindFluidbox();
						Time.Update();
						$newli.rebindHandlers(true).parent().reorderPosts();
						console.log(`[WS] Post added (postid=${data.type}-#${data.id}) to container ${this.section}`);
					}));
				}));
				conn.on('post-update', wsdecoder(function(data){
					if (!data.type || !data.id)
						return;

					let postid = `${data.type}-${data.id}`,
						$post = $(`#${postid}:not(.deleting)`);
					console.log('[WS] Post updated (postid=%s)', postid);
					if ($post.length)
						$post.reloadLi(false);
				}));
				conn.on('entry-score', wsdecoder(function(data){
					if (typeof data.entryid === 'undefined')
						return;

					let $entry = $(`#entry-${data.entryid}`);
					console.log('[WS] Entry score updated (entryid=%s, score=%s)', data.entryid, data.score);
					if ($entry.length)
						$entry.refreshVoting();
				}));
				conn.on('disconnect',function(){
					auth = false;
					console.log('[WS] %cDisconnected','color:red');
				});
			};
			if (!window.io)
				$.ajax({
					url: `${connpath}socket.io/socket.io.js`,
					cache: 'true',
					dataType: 'script',
					success: success,
					statusCode: {
						404: function(){
							console.log('%c[WS] Server down!','color:red');
							$.WS.down = true;
							$sidebar.find('.notif-list').on('click','.mark-read', function(e){
								e.preventDefault();

								$.Dialog.fail('Mark notification read','The notification server appears to be down. Please <a class="send-feedback">let us know</a>, and sorry for the inconvenience.');
							});
						}
					}
				});
			else success();
		}
		wsNotifs();
		$.WS = (function(){
			let dis = () => wsNotifs(),
				substatus = {
					postUpdates: false,
					entryUpdates: false,
				};
			dis.down = false;
			dis.navigate = function(){
				if (typeof conn === 'undefined')
					return;

				const page = location.pathname+location.search+location.hash;

				conn.emit('navigate',{page});
			};
			dis.recvPostUpdates = function(subscribe){
				if (typeof conn === 'undefined')
					return setTimeout(function(){
						dis.recvPostUpdates(subscribe);
					},2000);

				if (typeof subscribe !== 'boolean' || substatus.postUpdates === subscribe)
					return;
				conn.emit('post-updates',String(subscribe),wsdecoder(function(data){
					if (!data.status)
						return console.log('[WS] %cpost-updates subscription status change failed (subscribe=%s)', 'color:red', subscribe);

					substatus.postUpdates = subscribe;
					$('#episode-live-update')[substatus.postUpdates?'removeClass':'addClass']('hidden');
					console.log('[WS] %c%s','color:green', data.message);
				}));
			};
			dis.recvEntryUpdates = function(subscribe){
				if (typeof conn === 'undefined')
					return setTimeout(function(){
						dis.recvEntryUpdates(subscribe);
					},2000);

				if (typeof subscribe !== 'boolean' || substatus.entryUpdates === subscribe)
					return;
				conn.emit('entry-updates',String(subscribe),wsdecoder(function(data){
					if (!data.status)
						return console.log('[WS] %centry-updates subscription status change failed (subscribe=%s)', 'color:red', subscribe);

					substatus.entryUpdates = subscribe;
					$('#entry-live-update')[substatus.entryUpdates && window.EventType === 'contest'?'removeClass':'addClass']('hidden');
					console.log('[WS] %c%s','color:green', data.message);
				}));
			};
			dis.authme = function(){
				if (typeof conn === 'undefined' || auth === true)
					return;

				console.log(`[WS] %cReconnection needed for identity change`,'color:teal');
				conn.disconnect(0);
				setTimeout(function(){
					conn.connect();
				},100);
			};
			dis.unauth = function(){
				if (typeof conn === 'undefined' || auth !== true)
					return;

				conn.emit('unauth',null,function(data){
					if (!data.status) return console.log('[WS] %cUnauth failed','color:red');

					auth = false;
					console.log(`[WS] %cAuthentication dropped`,'color:brown');
				});
			};
			dis.disconnect = function(reason){
				if (typeof conn === 'undefined')
					return;

				console.log(`[WS] Forced disconnect (reason=${reason})`);
				conn.disconnect(0);
			};
			dis.status = function(){
				if (typeof conn === 'undefined')
					return setTimeout(function(){
						dis.status();
					},2000);

				conn.emit('status',null,wsdecoder(function(data){
					console.log('[WS] Status: ID=%s; Name=%s; Rooms=%s',data.User.id,data.User.name,data.rooms.join(','));
				}));
			};
			dis.devquery = function(what, data = {}, cb = undefined){
				if (typeof conn === 'undefined')
					return setTimeout(function(){
						dis.devquery(what, data, cb);
					},2000);

				conn.emit('devquery',{what,data},wsdecoder(function(data){
					if (typeof cb === 'function')
						return cb(data);

					console.log('[WS] DevQuery '+(data.status?'Success':'Fail'), data);
				}));
			};
			return dis;
		})();
	})();

	$.Navigation._docReady();
	console.log('%cDocument ready handlers called','color:green');
	console.groupEnd();
});

// Remove loading animation from header on load
$w.on('load',function(){
	'use strict';
	$body.removeClass('loading');
});
