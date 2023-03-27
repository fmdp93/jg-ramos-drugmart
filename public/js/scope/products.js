import { objBarcodeReader } from "/js/scope/barcode_reader.js";
import { toggletableEmpty } from "/js/function.js";
import { SupplierSearchAutocomplete } from "/js/decorator/SupplierSearchAutocomplete.js";
import { ProductPrice } from "/js/class/ProductPrice.js";

class Products {
    constructor() {
        // Table Empty vars
        this.$products_list = $("#products_list");
        this.$tbody = $("#products_list tbody");
        this.$table_empty = $(".table-empty");

        // update form vars
        this.deleteClicked = false;
        this.$product_id = $("#product_id");
        this.$item_code = $("#item_code");
        this.$item_name = $("#item_name");
        this.$base_price = $("#base_price");
        this.$markup = $("#markup");
        this.$selling_price = $("#selling_price");
        this.$expiration_date = $("#expiration_date");

        // Class vars
        this.$pages = $("#pages");
        this.$search = $("#search");
        this.$category = $("#category_id");
        this.ObjectSearch = new Search(product_search_url);
        this.$action = $("#action");
        this.$action_print_barcode = $("#action_print_barcode");

        // BarcodeReader vars
        this.$item_code = $("#item_code");
        this.objBarcodeReader = objBarcodeReader;
        this.objBarcodeReader.$search = this.$search;
        this.objBarcodeReader.then_callback =
            this.objBarcodeReader.changeSearch;
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

        this.ProductPrice = new ProductPrice();
        this.triggerEvents();
    }

    triggerEvents() {
        this.$search.on("keyup", "", { this: this }, _.throttle(this.requestProduct, 500));
        this.$category.on("change", "", { this: this }, this.requestProduct);
        this.$products_list.on("click", "tbody tr", this.fillUpdateForm);
        $(".delete-cell button").on("click", function () {
            _this.deleteClicked = true;
        });
    }

    fillUpdateForm(event) {
        if (_this.deleteClicked) {
            _this.deleteClicked = false;
            return;
        }
        let product_id = $(this).find("input[name='product_id']").val();

        _this.$product_id.val(product_id);
        _this.$item_code.val($(this).find("input[name='item_code']").val());
        _this.$item_name.val($(this).find("input[name='p_name']").val());
        _this.$base_price.val($(this).find("input[name='base_price']").val());        
        _this.$markup.val($(this).find("input[name='markup']").val());
        _this.$selling_price.val($(this).find("input[name='selling_price']").val());
        _this.$expiration_date.val(
            $(this).find("input[name='expiration_date']").val()
        );

        $.get(`/product/${product_id}/`, {}, function (response) {
            let parsed_response = JSON.parse(response);
            _this.$supplier_search.val("");
            _this.$supplier_search_id.val(parsed_response.supplier.s_id);
            _this.$vendor.val(parsed_response.supplier.vendor);
            _this.$company.val(parsed_response.supplier.company_name);
            _this.$contact.val(parsed_response.supplier.contact_detail);
            _this.$address.val(parsed_response.supplier.address);            
        });
    }

    requestProduct(event) {
        let category_id = _this.$category.val();

        let objSearchParam = {
            q: _this.$search.val(),
            category_id: category_id,
            action: _this.$action.val(),
            action_print_barcode: _this.$action_print_barcode.val(),
        };

        _this.ObjectSearch.appendParam(objSearchParam);

        $.get(
            _this.ObjectSearch.url,
            _this.ObjectSearch.param,
            _this.requestProductResponse
        );
    }

    requestProductResponse(response) {
        response = JSON.parse(response);
        _this.$tbody.html(response.rows_html);
        _this.$pages.html(response.links_html);
        toggletableEmpty(_this.$tbody, _this.$table_empty);
    }
}

let objProducts = new Products();
const _this = objProducts;
