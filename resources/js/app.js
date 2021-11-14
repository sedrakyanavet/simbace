import Vue from 'vue';
import {BootstrapVue, BootstrapVueIcons} from "bootstrap-vue";

import "bootstrap/dist/css/bootstrap.css";
import "bootstrap-vue/dist/bootstrap-vue.css";

window.axios = require('axios')

Vue.use(BootstrapVue)
Vue.use(BootstrapVueIcons)


// Register Vue Components
Vue.component('new-table', require('../components/NewTable.vue').default);
Vue.component('color-picker', require('../components/ColorPicker.vue').default);

// Initialize Vue
const app = new Vue({
    el: '#app',
});



// var app = new Vue({
//     el: "#app",
//     data: {
//         invoice_subtotal: 0,
//         invoice_total: 0,
//         invoice_tax: 5,
//         invoice_products: [{
//             product_no: '',
//             product_name: '',
//             product_price: '',
//             product_qty: '',
//             line_total: 0
//         }]
//     },
//
//     methods: {
//         saveInvoice() {
//             console.log(JSON.stringify(this.invoice_products));
//         },
//         calculateTotal() {
//             var subtotal, total;
//             subtotal = this.invoice_products.reduce(function (sum, product) {
//                 var lineTotal = parseFloat(product.line_total);
//                 if (!isNaN(lineTotal)) {
//                     return sum + lineTotal;
//                 }
//             }, 0);
//
//             this.invoice_subtotal = subtotal.toFixed(2);
//
//             total = (subtotal * (this.invoice_tax / 100)) + subtotal;
//             total = parseFloat(total);
//             if (!isNaN(total)) {
//                 this.invoice_total = total.toFixed(2);
//             } else {
//                 this.invoice_total = '0.00'
//             }
//         },
//         calculateLineTotal(invoice_product) {
//             var total = parseFloat(invoice_product.product_price) * parseFloat(invoice_product.product_qty);
//             if (!isNaN(total)) {
//                 invoice_product.line_total = total.toFixed(2);
//             }
//             this.calculateTotal();
//         },
//         deleteRow(index, invoice_product) {
//             var idx = this.invoice_products.indexOf(invoice_product);
//             console.log(idx, index);
//             if (idx > -1) {
//                 this.invoice_products.splice(idx, 1);
//             }
//             this.calculateTotal();
//         },
//         addNewRow() {
//             this.invoice_products.push({
//                 product_no: '',
//                 product_name: '',
//                 product_price: '',
//                 product_qty: '',
//                 line_total: 0
//             });
//         }
//     }
// });
