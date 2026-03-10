// Remember Tab State
$(function() {
	$('#mainTabs a:first').tab('show'); // Select first tab
	$('#langtabs a:first').tab('show');
	if (window.localStorage && window.localStorage['currentTab']) {
		$('.mainMenuTabs a[href="'+window.localStorage['currentTab']+'"]').tab('show');
	}
	if (window.localStorage && window.localStorage['currentSubTab']) {
		$('a[href="'+window.localStorage['currentSubTab']+'"]').tab('show');
	}
	$('.fadeInOnLoad').css('visibility','visible');
	$('.mainMenuTabs a[data-toggle="tab"]').click(function() {
		if (window.localStorage) {
			window.localStorage['currentTab'] = $(this).attr('href');
		}
	});
	$('a[data-toggle="tab"]:not(.mainMenuTabs a[data-toggle="tab"], .langtabs a[data-toggle="tab"])').click(function() {
		if (window.localStorage) {
			window.localStorage['currentSubTab'] = $(this).attr('href');
		}
	});
});

// Show & Hide Tabs
$(function() {
    var $typeSelector = $('#Checker');
    var $toggleArea = $('.module__');
	var $toggleArea2 = $('#settingsTab');
	var $toggleArea3 = $('#bundlesTab');
	 if ($typeSelector.val() === 'yes') {
            $toggleArea.show(); 
			$toggleArea2.show();
			$toggleArea3.show();
        }
        else {
            $toggleArea.hide(); 
			$toggleArea2.hide();
			$toggleArea3.hide();
        }
    $typeSelector.change(function(){
        if ($typeSelector.val() === 'yes') {
            $toggleArea.show(300); 
			$toggleArea2.show(300);
			$toggleArea3.show(300);
        }
        else {
            $toggleArea.hide(300); 
			$toggleArea2.hide(300);
 			$toggleArea3.hide(300);
        }
    });
});

$(function() {
    var $typeSelector1 = $('#LinkChecker');
    var $toggleArea1 = $('#MainLinkOptions');
	 if ($typeSelector1.val() === 'yes') {
            $toggleArea1.show(); 
        }
        else {
            $toggleArea1.hide(); 
        }
    $typeSelector1.change(function(){
        if ($typeSelector1.val() === 'yes') {
            $toggleArea1.show(300); 
        }
        else {
            $toggleArea1.hide(300); 
        }
    });
});