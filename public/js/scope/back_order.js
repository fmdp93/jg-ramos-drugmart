import { toggletableEmpty } from "/js/function.js";

class BackOrder{
    constructor(){
        // Search variables
        this.ObjectSearch = new Search(search_url);
        this.$q = $("#q");
        this.$input_from = $("#from");
        this.$input_to = $("#to");
        this.$table = $("#product_list");
        this.$tbody = $("#product_list tbody");
        this.$table_empty = $(".table-empty");
        this.$pages = $("#pages");
        
        this.triggerEvents();
    }

    triggerEvents(){
        this.$q.on("keyup", this.requestProduct);
    }

    
    requestProduct(event) {
        let objSearchParam = {
            q: _this.$q.val(),
            from: _this.$input_from.val(),
            to: _this.$input_to.val(),
        };

        _this.ObjectSearch.appendParam(objSearchParam);

        $.get(
            _this.ObjectSearch.url,
            _this.ObjectSearch.param,
            function (response) {
                _this.requestProductResponse(response);
            }
        );
    }

    requestProductResponse(response) {
        response = JSON.parse(response);
        this.$tbody.html(response.rows_html);
        this.$pages.html(response.links_html);        
        toggletableEmpty(_this.$tbody, _this.$table_empty);
    }
}

let objBackOrder = new BackOrder();
const _this = objBackOrder;