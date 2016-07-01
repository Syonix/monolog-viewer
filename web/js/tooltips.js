function initTooltipMouseleave() {
    var btns = document.querySelectorAll('.clipboard');

    for (var i = 0; i < btns.length; i++) {
        btns[i].addEventListener('mouseleave', function(e) {
            e.currentTarget.setAttribute('class', 'clipboard');
            e.currentTarget.removeAttribute('aria-label');
        });
    }
}

function initTooltips() {
    setTimeout(function () {
        initTooltipMouseleave();
    }, 200); // Dirty hack... :(
}

function showTooltip(elem, msg, color) {
    elem.setAttribute('class', 'clipboard tooltipped tooltipped-'+color);
    elem.setAttribute('aria-label', msg);
}

function fallbackMessage() {
    var actionMsg = '';

    if(/iPhone|iPad/i.test(navigator.userAgent)) {
        actionMsg = 'Not supported yet :(';
    }
    else if (/Mac/i.test(navigator.userAgent)) {
        actionMsg = 'Press ⌘-C to copy';
    }
    else {
        actionMsg = 'Press Ctrl-C to copy';
    }

    return actionMsg;
}
