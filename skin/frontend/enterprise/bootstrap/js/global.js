(function(a) {
    if (typeof b === "undefined") {
        var b = {
            init: function() {
                a("#logo").on("click", b.clickHandler)
            },
            clickHandler: function() {
                alert("OOPs there goes the namespace")
            }
        }
    }
    if (typeof c === "undefined") {
        var c = {
            init: function() {
                console.log("Running MyNameSpace.init")
            }
        }
    }
    a(function() {
        a("nav a").each(function() {
            var b = a(this);
            if (b.length > 0) {
                if (b.attr("href") == "#") {
                    a(this).click(function(a) {
                        a.preventDefault()
                    })
                }
            }
        });
        a(window).scroll(function() {
            if (a(this).scrollTop() != 0) {
                a("#toTop").fadeIn()
            } else {
                a("#toTop").fadeOut()
            }
        });
        a("#toTop a").click(function(b) {
            b.preventDefault();
            a("body,html").animate({
                scrollTop: 0
            }, 800)
        })
    })
})(jQuery)