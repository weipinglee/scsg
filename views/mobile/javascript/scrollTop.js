var SCROLLTOP = {
    allScrollGoTop: function(o) {
        "undefined" == typeof o && (o = "goTop_index");
        window.onscroll = function() {
            var t = $(document).scrollTop(),
            n = $('<div id="' + o + '" class="goTop_img" ontouchend="SCROLLTOP.goTop(this)"></div>');
            t > 0 ? 0 == $(".goTop_img").length && ($(".viptg-cont").css("bottom", "100px"), n.appendTo($("body"))) : ($(".goTop_img").remove(), $(".viptg-cont").css("bottom", "80px"))
        }
    },
    goTop: function(o) {
        var t = $(window.opera ? "CSS1Compat" == document.compatMode ? "html": "body": "html,body");
        $(o).on("click", 
        function() {
            t.animate({
                scrollTop: 0
            },
            600)
        })
    },
    goToDiv: function(o, t) {
        var n = $(window.opera ? "CSS1Compat" == document.compatMode ? "html": "body": "html,body");
        $(o).on("click", 
        function() {
            n.animate({
                scrollTop: $(t).offset().top
            },
            600)
        })
    }
};
SCROLLTOP.allScrollGoTop();