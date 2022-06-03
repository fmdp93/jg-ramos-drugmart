import { objBarcodeReader } from "/js/scope/barcode_reader.js";
import { toggletableEmpty } from "/js/function.js";
import { SupplierSearchAutocomplete } from "/js/decorator/SupplierSearchAutocomplete.js";

class PurchaseOrder {
    constructor() {
        // const _this = this;
        this.$item_code = $("#item_code");
        this.$quantity = $("#s_quantity");
        this.$tbody = $("#products_list tbody");
        this.$table_empty = $(".table-empty");
        this.$products_list = $("#products_list");
        this.$add_item = $("#add-item");
        this.$add_supplier_items = $("#add-supplier-items");
        this.$total = $("#total");
        this.$input_total = this.$total.find("input");
        this.$shipping_fee = $("#shipping_fee");
        this.$tax = $("#tax");
        this.$clear_table = $("#clear-table");
        this.objBarcodeReader = objBarcodeReader;
        this.objBarcodeReader.$item_code = this.$item_code;
        this.objBarcodeReader.then_callback =
            this.objBarcodeReader.changeItemCode;
        this.objBarcodeReader.done_callback = this.requestProduct;

        this.$vendor = $("#vendor");
        this.$company = $("#company");
        this.$contact = $("#contact");
        this.$address = $("#address");
        this.$supplier_search_id = $("#supplier_search_id");

        // autocomplete
        this.$supplier_search = $("#supplier_search");
        this.objSupplierSearchAutocomplete = new SupplierSearchAutocomplete(
            this
        );

        this.triggerEvents();
    }

    triggerEvents() {
        this.$shipping_fee.on("keyup change", this.updateTotal);
        this.$tax.on("keyup change", this.updateTotal);

        this.$clear_table.on("click", this.clearTable);
        this.$item_code.on("change keyup focus", this.requestProduct);
        this.$add_item.on("click", this.addProductToForm);
        $("#products_list").on(
            "change keyup",
            "input[name='quantity[]']",
            this.updateForm
        );
        $("#products_list").on("click", ".delete-item", this.deleteItem);
        this.$add_supplier_items.on("click", this.addSupplierItems)
    }

    addSupplierItems(event){
        event.preventDefault();
        let supplier_id = _this.$supplier_search_id.val();
        $.get('/purchase-order/supplier-search/', {supplier_id: supplier_id}, _this.response_addItemToTable)
    }

    clearTable(event) {
        _this.$tbody.html("");
        toggletableEmpty(_this.$tbody, _this.$table_empty);
    }

    deleteItem(event) {
        $(this).parents("tr").remove().promise().done(_this.updateTotal);
        toggletableEmpty(_this.$tbody, _this.$table_empty);
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
        $.get(
            "/purchase-order/search/",
            {
                item_code: _this.$item_code.val(),
                quantity: _this.$quantity.val(),
            },
            _this.requestProductResponse
        );
    }

    addProductToForm(event) {
        event.preventDefault();
        let quantity = parseInt(_this.$quantity.val());
        let item_code = _this.$item_code.val();
        let item_code_row = _this.$tbody
            .find(`input[value="${item_code}"]`)
        let found_item_code = item_code_row?.val();

        if (found_item_code == item_code) {
            let $row_quantity = item_code_row.parents('tr').find('input[name="quantity[]"]')
            let row_current_quantity = $row_quantity.val();
            row_current_quantity = parseInt(row_current_quantity);
            $row_quantity.val(row_current_quantity + quantity);
            return;
        }

        $.get(
            "/purchase-order/search/",
            {
                item_code: item_code,
                quantity: quantity,
            },
            _this.response_addItemToTable
        )
            .promise()
            .done(_this.updateTotal);
    }

    response_addItemToTable(response) {
        let parsed_response = JSON.parse(response);
        if (parsed_response?.tbody) {
            _this.$tbody.append(parsed_response.tbody);

            toggletableEmpty(_this.$tbody, _this.$table_empty);
        }
    }

    requestProductResponse(response) {
        let parsed_response = JSON.parse(response);
        if (parsed_response?.result?.p_id) {
            let result = parsed_response.result;
            $("#name").val(result.p_name);
            $("#description").val(result.description);
            $("#s_price").val(result.price);
        } else {
            // console.log("not found");
        }
        // console.log(parsed_response.last_query);
    }


    updateTotal() {
        let total = 0;
        let shipping_fee = parseFloat(_this.$shipping_fee.val()) || 0;
        let tax = parseFloat(_this.$tax.val()) || 0;

        $("#products_list input[name='quantity[]']").each(function () {
            let price = parseFloat(
                $(this).parents("tr").find("input[name='price[]']").val()
            );
            let quantity = parseFloat($(this).val() || 0);
            total += price * quantity;
        });

        total += shipping_fee + tax;
        _this.$input_total.val(sprintf("%.2f", total));
        _this.$total.find("span").html(sprintf("%.2f", total));
    }
}

let objPurchaseOrder = new PurchaseOrder();
const _this = objPurchaseOrder;
