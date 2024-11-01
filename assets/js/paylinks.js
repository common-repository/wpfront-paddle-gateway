(function () {
    
    function attach_paylinks() {
        if (typeof jQuery === "undefined") {
            setTimeout(attach_paylinks, 100);
            return;
        }

        jQuery(function () {
            if (typeof window.wpfront_paddle_gateway_paylink_data === "undefined") {
                console.log("'wpfront_paddle_gateway_paylink_data' is missing.");
                return;
            }

            var $ = jQuery;
            $(".wpfront-paddle-gateway-paylink").on("click", function () {
                var $this = $(this);
                $this.off("click").attr('href', "javascript: void(0);").addClass('loading');
                
                var data = {
                    "action": "wpfront_paddle_gateway_paylink",
                    "id": $this.data("id")
                };
                
                var price = $this.data("price");
                if(typeof price !== "undefined") {
                    data["price"] = price;
                }
                
                var title = $this.data("title");
                if(typeof title !== "undefined") {
                    data["title"] = title;
                }
                
                var nonce = $this.data("nonce");
                if(typeof nonce !== "undefined") {
                    data["nonce"] = nonce;
                }
                
                $.post(window.wpfront_paddle_gateway_paylink_data.ajaxurl, data, function (result) {
                    window.location = result.url;
                }, "json");
            });
        });
    }
    
    attach_paylinks();
    
})();