function toggleMore(id) {
    var context = $('#context-'+id);
    var more = $('#more-'+id);
    if(context.is(':visible')) {
        context.slideUp(200);
        more.html('<i class="fa fa-plus-circle"></i>');
    } else {
        context.slideDown(200);
        more.html('<i class="fa fa-minus-circle"></i>');
    }
}

function filterContent(string) {
    var count = 0;
    var total = 0;
    $(".logline").each(function(){
        var search = string.toLowerCase().trim();
        var message = $(this).find(".message").text().toLowerCase().trim();
        var date = $(this).find(".date").text().toLowerCase().trim();
        if(message.indexOf(search) > -1 || date.indexOf(search) > -1) {
            $(this).show();
            count++
        } else {
            $(this).hide();
        }
        total++;
    });
    $("#filter-count").text(count);
    if(count < total) {
        $("#filter-meta").show();
        $("#filter-text-wrap").addClass("active");
        $("#filter-text-reset").show();
    } else {
        $("#filter-meta").hide();
        $("#filter-text-wrap").removeClass("active");
        $("#filter-text-reset").hide();
    }
}

function filterContentReset() {
    filterContent("");
    $("#filter-text input").val("");
    $("#filter-text-reset").hide();
}

$(document).ready(function() {
    $("div.context").hide();
    $("#filter-form-toggle").click(function() {
        var arrow = $("#filter-form-arrow");
        var dropdown = $("#filter-form-dropdown");
        if(dropdown.is(":visible")) {
            arrow.removeClass("fa-chevron-up");
            arrow.addClass("fa-chevron-down");
            $(this).removeClass("active");
            dropdown.slideUp(200);
        } else {
            arrow.removeClass("fa-chevron-down");
            arrow.addClass("fa-chevron-up");
            $(this).addClass("active");
            dropdown.slideDown(200);
        }
    });
    var filterquery = $("#filter-query");
    var timeout = null;
    filterquery.keyup(function() {
        if(timeout != null) { clearTimeout(timeout); }
        timeout = setTimeout(function() { filterContent(filterquery.val()); }, 200);
    });

    $("#filter-query").focus(function() {
        console.log("focus");
        $("#filter-text-wrap").addClass("focus");
    });

    $("#filter-query").blur(function() {
        console.log("blur");
        $("#filter-text-wrap").removeClass("focus");
    });
});
