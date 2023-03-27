import * as func from "/js/function.js";

class InventoryOrders {
    constructor() {
        this.order_received = [];
        this.$view_details = $("#inventory_orders .view-details");
        this.$io_tbody = $("#inventory_order_list tbody");
        this.$modal = $("#details-modal");
        this.$modal_tbody = $("#details-modal tbody");
        this.$body = $("body");

        this.triggerEvents();
    }

    triggerEvents() {
        this.$modal.on("shown.bs.modal", this.loadModal);
        this.$body.on("focusin", ".expiration_date", this.loadDatepicker);
        this.$body.on(
            "keydown change",
            "input[name='quantity']",
            function (event) {
                let isPlusMinus = func.preventPlusMinus(event);
                if (isPlusMinus === false) {
                    return false;
                }
                _this.updateSubtotal(event);
            }
        );
        this.$body.on("click", ".order-received", this.orderReceivedSubmit);
        this.$modal.on("hidden.bs.modal", function (event) {
            _this.$modal_tbody.html("");
            _this.loadInventoryOrdersList(event);
        });
        this.$body.on(
            "keydown change",
            "input[name='price']",
            function (event) {
                let isPlusMinus = func.preventPlusMinus(event);
                if (isPlusMinus === false) {
                    return false;
                }
                _this.updateSubtotal(event);
            }
        );
    }

    loadInventoryOrdersList(event) {
        $.get(
            "/inventory/get-inventory-order-processing",
            {},
            function (response) {
                let parsed_response = JSON.parse(response);
                _this.$io_tbody.html(parsed_response.inventory_orders_list);
            }
        );
    }

    orderReceivedSubmit(event) {
        let tr_parent = $(this).parents("tr");
        let io2p_id = tr_parent.find(".io2p_id").val();
        let $expiration_date = tr_parent.find(".expiration_date");
        let expiration_date = $expiration_date.val();
        let $quantity = tr_parent.find(".quantity");
        let quantity = $quantity.val();
        let $price = tr_parent.find(".price");
        let price = $price.val();
        let $item_code = tr_parent.find(".item_code");
        let item_code = $item_code.val();
        let $transaction_id = tr_parent.find(".transaction_id");
        let transaction_id = $transaction_id.val();
        let $product_id = tr_parent.find(".product_id");
        let product_id = $product_id.val();
        let token = tr_parent.find("input[name='_token']").val();
        let $order_received = $(this);

        if (_this.order_received[io2p_id]) {
            return;
        }

        let params = {
            _token: token,
            io2p_id: io2p_id,
            expiration_date: expiration_date,
            price: price,
            quantity: quantity,
            item_code: item_code,
            transaction_id: transaction_id,
            product_id: product_id,
        };
        $.post("/inventory/order-received", params, function (response) {
            let parsed_response = JSON.parse(response);
            let min_width = $order_received.width();
            $order_received.css("min-width", min_width);
            $order_received.html('<i class="fa-solid fa-circle-check"></i>');
            $expiration_date.prop("disabled", true);
            $quantity.prop("disabled", true);
            _this.order_received[io2p_id] = true;
        });
    }

    updateSubtotal(event) {
        let price = func.falsyToZero(
            $(this).parents("tr").find("input[name='price']").val()
        );
        let $subtotal = $(this).parents("tr").find(".subtotal");
        let subtotal = func.falsyToZero($subtotal.val());

        let defer = $.Deferred();
        let filtered = defer.then(
            function () {
                let qty = func.falsyToZero(
                    $(this).parents("tr").find("input[name='quantity']").val()
                );
                subtotal = parseFloat(price) * parseFloat(qty);
            }.bind(this)
        );

        defer.resolve();
        filtered.done(function () {
            $subtotal.html(sprintf("%.2f", subtotal));
        });
    }

    loadDatepicker(event) {
        $(this).datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: "yy-mm-dd",
            container: "#detailsModalContent",
        });
    }

    loadModal(event) {
        let io_id = $(event.relatedTarget).data("io-id");
        let supplier_id = $(event.relatedTarget).data("supplier-id");
        _this.order_received = [];
        $.get(
            "/inventory/order-products",
            { io_id: io_id, supplier_id: supplier_id },
            function (response) {
                let parsed = JSON.parse(response);
                _this.$modal_tbody.html(parsed.modal_content);
            }
        );
    }
}

let objInventoryOrders = new InventoryOrders();
const _this = objInventoryOrders;
