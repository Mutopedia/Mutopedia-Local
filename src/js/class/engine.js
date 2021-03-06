var Engine = {
  documentTitle: "",
  userNumberCount: 0,

  init: function(){
    this.documentTitle = document.title;

    $.post(App.phpPath+"app.php", { action: "init"}, function(data){
      if(data.result){
        console.log(data.result);
      }
  	}, "json");
  },

  getSpecimenSprite: function(specimenNameCode){
    $.post(App.phpPath+"app.php", { action: "getSpecimenSprite", specimenNameCode: specimenNameCode}, function(data){

        console.log(data.result);

  	}, "json");
  },

  fileExists: function(filePath){
    if(filePath){
      var http = new XMLHttpRequest();
      http.open('HEAD', filePath, false);
      http.send();
      return http.status==200;
    }else {
      return false;
    }
  },

  historyPushState: function(modelName, argName){
    document.title = this.documentTitle + ' | ' + modelName.toUpperCase();
    if (modelName) {
      if(argName == null) {
        window.history.pushState({'pageName': modelName}, modelName, modelName);
      }else{
        window.history.pushState({'pageName': modelName, 'argName': argName}, modelName+'/'+argName, modelName+'/'+argName);
      }
    }
  },

  AcceptCookies: function(){
  	var today = new Date(), expires = new Date();
  	expires.setTime(today.getTime() + (365*24*60*60*1000));
  	document.cookie = "AcceptationCookies =" + encodeURIComponent(1) + ";expires=" + expires.toGMTString();

  	Interface.closePopUp();
  },

  getCookie: function(cookieName){
      var oRegex = new RegExp("(?:; )?" + cookieName + "=([^;]*);?");

  	if (oRegex.test(document.cookie)){
  		return decodeURIComponent(RegExp["$1"]);
  	}else {
  		return null;
  	}
  },

  checkAcceptationCookies: function(){
  	var cookieValue = getCookie('AcceptationCookies');

  	if(cookieValue != 1){
  		showPopUp('cookiesPrivacy');
  	}else {
  		closePopUp();
  	}
  },

  openInNewTab: function(url) {
    var win = window.open(url, '_blank');
    win.focus();
  },

  searchSpecimen: function(specimenName, searchDiv){
    $.post(App.phpPath+"app.php", { action: "searchSpecimen", specimenName: specimenName}, function(data){
  		searchDiv.parent().children('.ul').fadeOut(100).queue(function() {
  			$(this).html(data.reply).queue(function() {
  				$(this).fadeIn(100);
  				$(this).dequeue();
  			});
  			$(this).dequeue();
  		});
  	}, "json");
  },

  startBreeding: function(){
  	var specimenNameCode_1 = $('#left_panel .select').attr('value');
  	var specimenNameCode_2 = $('#right_panel .select').attr('value');

  	$('#odds_container').fadeOut(200).queue(function()
  	{
  		$(this).html('<h2 style="text-align: center;">Breeding in progress ...</h2>').fadeIn(200).queue(function()
  		{
  			$.post(App.phpPath+"app.php", { action: "startBreeding", specimenNameCode_1: specimenNameCode_1, specimenNameCode_2: specimenNameCode_2}, function(data)
  			{
  				if(data.result)
  				{
  					$('#odds_container').fadeOut(200).queue(function(){
  						$(this).html(data.reply).fadeIn(300);
  						$(this).dequeue();
  					});
  				}
  				else
  				{
  					$('#odds_container').fadeOut(200).queue(function(){
  						$(this).html(data.error).fadeIn(300);
  						$(this).dequeue();
  					});
  				}

  			}, "json");

  			$(this).dequeue();
  		});
  		$(this).dequeue();
  	});
  },

  logUser: function(userid, userfirstname, userlastname, userpic){
  	$.post(App.phpPath+"app.php", { action: "logUser", userid: userid, userfirstname: userfirstname, userlastname: userlastname, userpic: userpic}, function(data){
  		if(data.result){
  			console.log(data.reply);
  			Interface.loadHeader();
  		}
  		if(data.error != null){
  			console.log(data.error);
  		}

  	}, "json");
  },

  searchUsers: function(){
    var searchContent = $('#search-container #input-container input').val();
  	var sortByValue = $("#search-container #option-sort-container .select").attr('value');

  	$('#search-container #result-container').stop().fadeOut(100).html('<h2 style="text-align: center;">Loading ...</h2>').stop().fadeIn(200).queue(function(){
  		$.post(App.phpPath+"app.php", { action: "searchUsers", searchContent: searchContent, sortByValue: sortByValue, limitNumberStart: Engine.userNumberCount, limitNumber: 10}, function(data){
  			if(data.result){
  				$('#search-container #error-container').fadeOut(200).queue(function(){
  					$('#search-container #result-container').fadeOut(200).queue(function() {
  						$(this).html(data.reply).append('<div class="button" onclick="Engine.searchUsersScroll();"><p>Load More</p></div>').queue(function() {
  							$(this).fadeIn(400);
  							$(this).dequeue();
  						});
  						$(this).dequeue();
  					});
  					$(this).dequeue();
  				});
  			}else {
  				$('#search-container #result-container').fadeOut(200).queue(function(){
  					$('#search-container #error-container').fadeIn(200).queue(function() {
  						$(this).children('h2').fadeOut(400).queue(function() {
                  $(this).html(data.error).fadeIn(400);
                  $(this).dequeue();
      					});
  						$(this).dequeue();
  					});
  					$(this).dequeue();
  				});
  			}

  		}, "json");

  		$(this).dequeue();
  	});
  },

  searchUsersScroll(searchContent){
    var searchContent = $('#search-container #input-container input').val();
    var sortByValue = $("#search-container #option-sort-container .select").attr('value');

    Engine.userNumberCount = Engine.userNumberCount + 10;

		$.post(App.phpPath+"app.php", { action: "searchUsers", searchContent: searchContent, sortByValue: sortByValue, limitNumberStart: Engine.userNumberCount, limitNumber: 10}, function(data){
			if(data.result){
				$('#search-container #error-container').fadeOut(200).queue(function(){
            $('#search-container #result-container .button').fadeOut(100).remove();
						$('#search-container #result-container').append(data.reply).queue(function() {
              $(this).append('<div class="button" onclick="Engine.searchUsersScroll();"><p>Load More</p></div>')
							$('#search-container #result-container .button').fadeIn(200);
							$(this).dequeue();
						});
					$(this).dequeue();
				});
			}
    }, "json");
  },

  getReleaseCounter: function(releaseName){
  	$.post(App.phpPath+"app.php", { action: "getReleaseDate", releaseName: releaseName}, function(data)
  	{
  		$('#portal-container #counter-container #counter').html(data.reply);

  		console.log(data.reply);
  		if(data.error != null){
  			console.log(data.error);
  		}

  	}, "json");

  	setTimeout("getReleaseCounter('portal')", 1000);
  },

  changeFbPermission: function(state){
  	$.post(App.phpPath+"app.php", { action: "changeFbPermission", state: state}, function(data){
  		if(data.error != null){
  			console.log(data.error);
  		}else{
  			console.log(data.reply);
  		}
  	}, "json");
  },

  changeCharterAcceptance: function(state){
  	$.post(App.phpPath+"app.php", { action: "changeCharterAcceptance", state: state}, function(data){
  		if(data.error != null){
  			console.log(data.error);
  		}else{
  			console.log(data.reply);
  			console.log(data.return);

  			if(data.result){
  				if(data.return == 'true'){
  					$('#profile-container #infos-container #user-info-container #charter-acceptance-blocker').removeClass('activate').addClass('unactivate');
  				}else{
  					$('#profile-container #infos-container #user-info-container #charter-acceptance-blocker').removeClass('unactivate').addClass('activate');
  				}
  			}
  		}
  	}, "json");
  },

  changeUserMutant: function(mutantNameCode){
  	$.post(App.phpPath+"app.php", { action: "changeUserMutant", mutantNameCode: mutantNameCode}, function(data){
  		if(data.error != null){
  			console.log(data.error);
  		}else{
  			console.log(data.reply);
  		}
  	}, "json");
  },

  changeUserCenterLevel: function(centerLevel){
  	$.post(App.phpPath+"app.php", { action: "changeUserCenterLevel", centerLevel: centerLevel}, function(data)
  	{
  		if(data.error != null){
  			console.log(data.error);
  		}else{
  			console.log(data.reply);
  		}
  	}, "json");
  },

  changeUserFameLevel: function(fameLevel){
  	$.post(App.phpPath+"app.php", { action: "changeUserFameLevel", fameLevel: fameLevel}, function(data){
  		if(data.error != null){
  			console.log(data.error);
  		}else{
  			console.log(data.reply);
  		}
  	}, "json");
  },

  sendReport: function(reported_playerId){
  	var report_message = $('.popup-box#report-box .box-content #report_message').val();

  	$.post(App.phpPath+"app.php", { action: "reportPlayer", reported_playerId: reported_playerId, report_message: report_message}, function(data){
  		if(data.result){
  			$('.popup-box#report-box .box-content ul li:nth-child(2)').fadeOut(200);
  			$('.popup-box#report-box .box-content ul li:first-child p').fadeOut(200).html(data.reply).fadeIn(200);
  			$('.popup-box#report-box .box-content ul li:last-child .button').attr("onclick", "Interface.closePopUp('report-box');").children('p').html('Finish !');
  		}
  		if(data.error != null){
  			$('.popup-box#report-box .box-content ul li:first-child p').fadeOut(200).html(data.error).fadeIn(200);
  		}
  	}, "json");
  },

  sendUserMessage: function(toPlayerId){
  	var message_content = $('.popup-box#message_user-box .box-content #message_content').val();

  	$.post(App.phpPath+"app.php", { action: "sendUserMessage", toPlayerId: toPlayerId, message_content: message_content}, function(data){
  		if(data.result){
  			$('.popup-box#message_user-box .box-content ul li:nth-child(2)').fadeOut(200);
  			$('.popup-box#message_user-box .box-content ul li:first-child p').fadeOut(200).html(data.reply).fadeIn(200);
  			$('.popup-box#message_user-box .box-content ul li:last-child .button').attr("onclick", "Interface.closePopUp('message_user-box');").children('p').html('Finish !');
  		}
  		if(data.error != null){
  			$('.popup-box#message_user-box .box-content ul li:first-child p').fadeOut(200).html(data.error).fadeIn(200);
  		}
  	}, "json");
  }
}

$(window).scroll(function() {
  if(history.state.pageName !== 'portal'){
    if($(window).scrollTop() < $('#news-bar').offset().top){
      $('#menu-nav').css({'position': 'relative', 'margin-top': '0', 'box-shadow': 'none'});
    }
    else if(($(window).scrollTop() + 52) > $('#menu-nav').offset().top){
      $('header #header-container').css({'height': '121px'});
      $('#menu-nav').css({'position': 'fixed', 'margin-top': '-62px', 'box-shadow': '0px 0px 15px 10px rgba(0, 0, 0, 1)'});
    }
  }
});

$(window).ready(function() {
  if(typeof history.pushState == 'undefined'){
  	alert("Your browser is out of date !");
  }else{
    if(history.state.pageName == 'portal'){
  		getReleaseCounter('portal');
    }

    window.onpopstate = function(event){
      if(event.state){
        Interface.loadModel(event.state.pageName);
      }
    }
  }
});

$(window).resize(function(){
  Interface.newsBarTextScroll();
});

$.fn.textWidth = function(text, font){
	if (!$.fn.textWidth.fakeEl) $.fn.textWidth.fakeEl = $('<span>').hide().appendTo(document.body);
	$.fn.textWidth.fakeEl.text(text || this.val() || this.text()).css('font', font || this.css('font'));
	return $.fn.textWidth.fakeEl.width();
};
