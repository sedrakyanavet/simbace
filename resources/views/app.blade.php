<!doctype html>
<html>
<head>
    <link rel="stylesheet" href="{{ url('dist/app.css') }}" />
{{--    <link rel="stylesheet" href="{{ url('../../node_modules/bootstrap/scss/bootstrap.scss') }}" />--}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Dynamically Add or Remove Table Row Using VueJS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="/js/app.js" defer></script>
</head>

<body>
<div id="app">
    <new-table></new-table>
</div>

{{--<script src="{{ url('dist/app.js') }}"></script>--}}
{{--<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>--}}
{{--<script src="https://unpkg.com/axios/dist/axios.min.js"></script>--}}

{{--<div id="app">--}}
{{--    <table class="table">--}}
{{--        <thead>--}}
{{--        <tr>--}}
{{--            <th scope="col">#</th>--}}
{{--            <th scope="col">2</th>--}}
{{--            <th scope="col">3</th>--}}
{{--            <th scope="col">4</th>--}}
{{--            <th scope="col">5</th>--}}
{{--            <th scope="col">6</th>--}}
{{--        </tr>--}}
{{--        </thead>--}}
{{--        <tr v-for="(invoice_product, k) in invoice_products" :key="k">--}}
{{--        <td scope="row" class="trashIconContainer" align="center" valign="center">--}}
{{--            <i class="fa fa-trash" @click="deleteRow(k, invoice_product)"></i>--}}
{{--        </td>--}}
{{--        <td>--}}
{{--            <input class="form-control" type="text" v-model="invoice_product.product_no" />--}}
{{--        </td>--}}
{{--        <td>--}}
{{--            <input class="form-control" type="text" v-model="invoice_product.product_name" />--}}
{{--        </td>--}}
{{--        <td>--}}
{{--            <input class="form-control text-right" type="number" min="0" step=".01" v-model="invoice_product.product_price" @change="calculateLineTotal(invoice_product)"--}}
{{--            />--}}
{{--        </td>--}}
{{--        <td>--}}
{{--            <input class="form-control text-right" type="number" min="0" step=".01" v-model="invoice_product.product_qty" @change="calculateLineTotal(invoice_product)"--}}
{{--            />--}}
{{--        </td>--}}
{{--        <td>--}}
{{--            <input readonly class="form-control text-right" type="number" min="0" step=".01" v-model="invoice_product.line_total" />--}}
{{--        </td>--}}
{{--    </tr>--}}
{{--    </table>--}}
{{--    <button type='button' class="btn btn-info" @click="addNewRow">--}}
{{--        <i class="fas fa-plus-circle"></i>--}}
{{--        Add--}}
{{--    </button>--}}
{{--</div>--}}
</body>
{{--<script src="{{ mix('/js/app.js') }}"></script>--}}

{{--<script src="{{ url('dist/app.js') }}"></script>--}}
{{--<script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.js"></script>--}}
</html>
