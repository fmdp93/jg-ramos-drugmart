import { objBarcodeReader } from "/js/scope/barcode_reader.js";
import * as func from "/js/function.js";
import { ProductPrice } from "/js/class/ProductPrice.js";
import { DecorSupplier } from "/js/decorator/DecorSupplier.js";

class AddProduct {
    constructor() {        
        this.$stock = $("#stock");
        this.$inv_stock = $("#inv_stock");
        this.$name = $("#name");
        this.$description = $("#description");
        this.$category_id= $("#category_id");
        this.$unit= $("#unit");
        this.first_unit = this.$unit.find('option:first-child').val();
        this.$stock= $("#stock");
        this.$expiration_date= $("#expiration_date");
        this.first_category_id = this.$category_id.find('option:first-child').val();
        // BarcodeReader vars
        this.$item_code = $("#item_code");
        this.objBarcodeReader = objBarcodeReader;
        this.objBarcodeReader.$item_code = this.$item_code;
        this.objBarcodeReader.then_callback =
            this.objBarcodeReader.changeItemCode;
        this.objBarcodeReader.done_callback = this.getItemCodeDetails;

        this.$new_item_code = $("#new_item_code");

        // DecorSupplier vars        
        this.$vendor = $("#vendor");
        this.$company = $("#company");
        this.$contact = $("#contact");
        this.$address = $("#address");
        this.$supplier = $("#supplier");
        this.first_supplier = this.$supplier.find('option:first-child').val();
        this.DecorSupplier = new DecorSupplier(this);

        this.ProductPrice = new ProductPrice();
        this.triggerEvents();
    }

    triggerEvents() {
        this.$new_item_code.on("click", this.setCode);           
        this.$item_code.on("keyup change", this.getItemCodeDetails);
        this.$stock.on("keydown", func.preventPlusMinus);
        this.$inv_stock.on("keydown", func.preventPlusMinus);        
    }    



    getItemCodeDetails(event){
        $.get("/product/get-item-code-details", {item_code: _this.$item_code.val()}, function(response){
            let parsed_response = JSON.parse(response);

            if(parsed_response?.product?.p_id !== undefined){
                let product = parsed_response.product;
                _this.$name.val(product.p_name);
                _this.$description.val(product.description);
                _this.$category_id.val(product.c_id);                
                _this.$unit.val(product.unit);
                _this.$stock.val(product.stock);
                _this.$expiration_date.val(product.expiration_date);                
                _this.$vendor.val(product.vendor);
                _this.$company.val(product.company_name);
                _this.$contact.val(product.contact_detail);
                _this.$address.val(product.address);

                _this.ProductPrice.$base_price.val(product.base_price);
                _this.ProductPrice.$selling_price.val(product.price);
                _this.ProductPrice.$markup.val(product.markup);

                _this.$supplier.val(product.s_id);
            }else{
                _this.$name.val("");
                _this.$description.val("");
                _this.$category_id.val(_this.first_category_id);                
                _this.$unit.val(_this.first_unit);
                _this.$stock.val("");
                _this.$expiration_date.val("");                
                _this.$vendor.val("");
                _this.$company.val("");
                _this.$contact.val("");
                _this.$address.val("");

                _this.ProductPrice.$base_price.val("");
                _this.ProductPrice.$selling_price.val("");
                _this.ProductPrice.$markup.val(default_markup);

                _this.$supplier.val(_this.first_supplier);
            }
        });
    }

    setCode(event) {
        event.preventDefault();
        $.get("/product/get-item-code", {}, function (response) {
            let parsed = JSON.parse(response);
            _this.$item_code.val(parsed.new_item_code);
        });
    }
}

let objAddProduct = new AddProduct();
const _this = objAddProduct;
