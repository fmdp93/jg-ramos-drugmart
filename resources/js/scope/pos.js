import { objBarcodeReader } from "/js/scope/barcode_reader.js";
import * as func from "/js/function.js";
import { Pin } from "/js/class/pin.js";
import { CustomerSearchAutocomplete } from "/js/decorator/CustomerSearchAutocomplete.js";

var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
);
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

class POS {
    constructor() {
        // const _this = this;
        this.$item_code = $("#item_code");
        this.$s_item_name = $("#name");
        this.$quantity = $("#s_quantity");
        this.quantityTooltip;
        this.$tbody = $("#products_list tbody");
        this.$table_empty = $(".table-empty");
        this.$products_list = $("#products_list");
        this.$add_item = $("#add-item");
        this.$s_total = $("#s_total");
        this.$total = $("#total");
        this.$form_pos = $("#pos");
        this.$input_total = this.$total.find("input");
        this.$clear_table = $("#clear-table");
        this.$pay_cash = $("#pay-cash");
        this.$submit_pos = $("#submit_pos");
        this.$amount_paid = $("#amount_paid");
        this.$change = $("#change");
        this.$senior_discounted = $("#senior_discounted");
        this.$senior_discount = $("#senior_discount");

        // Barcode
        this.objBarcodeReader = objBarcodeReader;
        this.objBarcodeReader.$item_code = this.$item_code;
        this.objBarcodeReader.then_callback =
            this.objBarcodeReader.changeItemCode;
        this.objBarcodeReader.done_callback = this.requestProductById;

        this.$pos_error = $("#pos-error");

        // Pay cash Modal
        this.elemModal = $("#pay-cash-modal > .modal")[0];
        this.modal = new bootstrap.Modal(this.elemModal);

        this.$delete_item_form = $("#delete-item-form");
        this.Pin = new Pin(this);

        // autocomplete
        this.$customer_search = $("#customer_search");
        this.$customer_id = $("#customer_id");
        this.$customer_name = $("#customer_name");
        this.$customer_address = $("#customer_address");
        this.$customer_contact_detail = $("#customer_contact_detail");
        this.objCustomerSearchAutocomplete = new CustomerSearchAutocomplete(
            this
        );

        // Mode of Payment
        this.$mode_of_payment = $("#mode_of_payment");
        this.$gcash_num = $("#gcash_num");
        this.$cc_num = $("#cc_num");
        this.$gcash_inputs = $("#gcash_inputs");
        this.$cc_inputs = $("#cc_inputs");

        this.triggerEvents();
    }

    triggerEvents() {
        this.$s_item_name.on(
            "change keyup",
            "",
            {
                response: this.requestProductResponse,
            },
            this.requestProduct
        );

        this.$item_code.on("change keyup", this.requestProductById);

        this.$add_item.on("click", this.addItem);
        this.$quantity.on(
            "change keydown",
            "",
            {
                response: this.updateSearchSubtotal,
            },
            this.requestProduct
        );
        $("#products_list").on(
            "change keyup",
            "input[name='quantity[]']",
            this.updateForm
        );
        $("#products_list").on(
            "keydown",
            "input[name='quantity[]']",
            func.preventPlusMinus
        );
        this.$clear_table.on("click", this.clearTable);
        this.$pay_cash.on("click", this.showModal);
        this.$amount_paid.on("keyup change", this.isAmountValid);
        this.$amount_paid.on("keydown", func.preventPlusMinus);
        this.$submit_pos.on("click", this.submitPos);

        this.elemModal.addEventListener("hide.bs.modal", function (event) {
            _this.$pos_error.addClass("d-none");
        });

        this.elemModal.addEventListener("shown.bs.modal", this.toggleModeOfPaymentInputs);

        this.Pin.shown();

        this.$delete_item_form.on("submit", this.deleteItem);
        this.$senior_discounted.on("change", this.applySeniorDiscount);

        this.$mode_of_payment.on("change", function(event){
            _this.isAmountValid(event);
            _this.toggleModeOfPaymentInputs(event);
        });
        this.$gcash_num.on("keydown", func.preventPlusMinus);
        this.$cc_num.on("keydown", func.preventPlusMinus);
        this.isShowModal();
    }

    isShowModal() {
        if (show_modal) {
            this.modal.show();
        }
    }

    toggleModeOfPaymentInputs(event) {
        let mode = _this.$mode_of_payment.val();
        if (mode == MODE_CASH) {
            _this.$gcash_inputs.addClass("d-none");
            _this.$cc_inputs.addClass("d-none");
        }
        if (mode == MODE_GCASH) {
            _this.$gcash_inputs.addClass("d-block").removeClass("d-none");
            _this.$cc_inputs.addClass("d-none").removeClass("d-block");
        }
        if (mode == MODE_CREDIT_CARD) {
            _this.$gcash_inputs.addClass("d-none").removeClass("d-block");
            _this.$cc_inputs.addClass("d-block").removeClass("d-none");
        }
    }

    isAmountValid(event) {
        _this.$change.val("");
        let total = parseFloat(_this.$total.find("input").val()) || 0;
        let amount_paid = parseFloat(_this.$amount_paid.val()) || 0;
        let change = amount_paid - total;

        // if not enough funds to pay
        if (amount_paid <= 0) {
            //error
            if (amount_paid != "") {
                _this.showPosError("Not enough funds");
            }
            return "invalid";
        }

        // if not enough funds to pay
        if(amount_paid <= 0 && amount_paid != ""){
            _this.showPosError("Minimum amount paid is 1");
            return "invalid";
        }

        if (amount_paid < total && _this.$mode_of_payment.val() != MODE_CREDIT_CARD) {            
            //error
            _this.showPosError("Not enough funds");
            return "invalid";
        }

        _this.setChange(change)
         
        _this.$pos_error.addClass("d-none");
        return true;
    }

    setChange(change){
        if(change >= 0){
            _this.$change.val(sprintf("%.2f", change));
        }else{
            _this.$change.val("0.00");
        }      
    }

    submitPos(event) {
        event.preventDefault();
        let total = parseFloat(_this.$total.find("input").val()) || 0;
        // check if cart is empty - tbody content
        if (total <= 0) {
            //error
            _this.showPosError("Cart is empty");
            return;
        }

        if (_this.isAmountValid(event) == "invalid") {
            return;
        }
        _this.$form_pos.trigger("submit");
    }

    showPosError(message) {
        this.$pos_error.html(message);
        this.$pos_error.removeClass("d-none");
    }

    showModal(event) {
        event.preventDefault();
        _this.$change.val("");
        _this.$amount_paid.val("");
        _this.modal.show();
    }

    updateSearchSubtotal(response) {
        let parsed = JSON.parse(response);
        let quantity = _this.$quantity.val();
        _this.quantityTooltip?.dispose();

        if (parsed.result?.p_id) {
            let price = parsed.result.price;
            _this.$s_total.val(sprintf("%.2f", price * quantity));
        }
    }

    clearTable(event) {
        // event.preventDefault();
        _this.$tbody.html("");
        func.toggletableEmpty(_this.$tbody, _this.$table_empty);
    }

    deleteItem(event) {
        event.preventDefault();
        _this.Pin.isPinCorrect(function () {
            _this.Pin.$triggeredDeleteButton
                .parents("tr")
                .remove()
                .promise()
                .done(_this.updateTotal);
            func.toggletableEmpty(_this.$tbody, _this.$table_empty);
        });
    }

    updateForm(event) {
        let $this = $(this);
        let parentTable = "#products_list";
        let price = $this
            .parents(parentTable)
            .find("input[name='price[]']")
            .val();
        let quantity = $this.val();
        let subtotal = price * quantity;

        let data = {
            $this: $this,
            parentTable: parentTable,
            price: price,
            quantity: quantity,
            subtotal: subtotal,
        };

        _this.updateSubtotal(event, data);
        _this.updateTotal();
    }

    updateSubtotal(event, data) {
        data.$this
            .parents("tr")
            .find(".subtotal")
            .html(sprintf("%.2f", data.subtotal));
    }

    requestProduct(event) {
        if (_this.isPlusMinus(event.keyCode)) {
            return false;
        }

        let response = event.data.response;
        $.get(
            "/pos/inventory-search",
            { item_name: _this.$s_item_name.val() },
            response
        );
    }

    isPlusMinus(key) {
        let prevented_keys = [109, 107, 189, 187];
        if ($.inArray(key, prevented_keys) > -1) {
            return true;
        }
    }
    requestProductById() {
        $.get(
            "/pos/inventory-search",
            { item_code: _this.$item_code.val() },
            _this.requestProductByIdResponse
        );
    }

    addItem(event) {
        event.preventDefault();
        $.get(
            "/pos/get-table-row",
            {
                item_code: _this.$item_code.val(),
                quantity: _this.$quantity.val(),
                form: _this.$form_pos.attr("id"),
            },
            _this.addItemBtnResponse
        )
            .promise()
            .then(function () {
                _this.quantityTooltip?.toggle();
            })
            .then(_this.updateTotal);
    }

    addItemBtnResponse(response) {
        let parsed_response = JSON.parse(response);
        let quantity = _this.$quantity.val();
        let result = parsed_response.result;

        // check stock
        if (result?.p_id) {
            let item_code = _this.$item_code.val();
            let table_stock_array = _this.$tbody
                .find(`input[value='${item_code}']`)
                .parents("tr")
                .find(`input[name='quantity[]']`);
            let table_stock = 0;
            if (table_stock_array.length > 0) {
                table_stock_array.each(function () {
                    table_stock += parseFloat($(this).val());
                });
            }

            let inventory_stock = result.i_stock - table_stock;
            if (quantity > inventory_stock) {
                _this.quantityTooltip = new bootstrap.Tooltip(
                    _this.$quantity[0],
                    {
                        title: "Not enough stock",
                    }
                );

                return;
            }
        }

        if (parsed_response?.tbody) {
            _this.$tbody.append(parsed_response.tbody);

            func.toggletableEmpty(_this.$tbody, _this.$table_empty);
        }
    }

    requestProductResponse(response) {
        let parsed_response = JSON.parse(response);

        _this.$quantity.val("");
        _this.$s_total.val("");
        if (parsed_response?.result?.p_id) {
            let result = parsed_response.result;
            _this.$item_code.val(result.item_code);
            $("#description").val(result.description);
            $("#s_price").val(sprintf("%.2f", result.price));
            $("#s_stock").val(result.i_stock);
        } else {
            _this.$item_code.val("");
            $("#description").val("");
            $("#s_price").val("");
            $("#s_stock").val("");
        }
    }

    requestProductByIdResponse(response) {
        let parsed_response = JSON.parse(response);

        _this.$quantity.val("");
        _this.$s_total.val("");
        if (parsed_response?.result?.p_id) {
            let result = parsed_response.result;
            $("#name").val(result.p_name);
            $("#description").val(result.description);
            $("#s_price").val(result.price);
            $("#s_stock").val(result.i_stock);
        } else {
            $("#name").val("");
            $("#description").val("");
            $("#s_price").val("");
            $("#s_stock").val("");
        }
    }

    updateTotal() {
        let total = 0;
        $("#products_list input[name='quantity[]']").each(function () {
            let price = parseFloat(
                $(this).parents("tr").find("input[name='price[]']").val()
            );
            let quantity = parseFloat($(this).val() || 0);
            total += price * quantity;
        });

        //senior discount
        if (_this.$senior_discounted.is(":checked")) {
            total = total * (1 - parseFloat(_this.$senior_discount.val()));
        }

        _this.$input_total.val(sprintf("%.2f", total));
        _this.$total.find("span").html(sprintf("%.2f", total));
    }

    applySeniorDiscount() {
        let defer = $.Deferred();
        let filtered = defer.then(_this.updateTotal);
        defer.resolve();
        filtered.done(_this.isAmountValid);
    }
}

let objPOS = new POS();
const _this = objPOS;
