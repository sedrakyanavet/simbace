<template>
    <b-container class="mt-2">
        <b-table-simple hover responsive>
            <b-thead head-variant="dark">
                <b-tr>
                    <b-th>STATUS</b-th>
                    <b-th>ACCOMMODATION</b-th>
                    <b-th>NUMBER OF ROOMS</b-th>
                    <b-th>ROOM TYPE</b-th>
                    <b-th>CHECK-IN-DATE</b-th>
                    <b-th>NTS</b-th>
                    <b-th>PRICE €</b-th>
                    <b-th>COST €</b-th>
                </b-tr>
            </b-thead>
            <b-tbody>
                <b-tr v-for="(item, index) in items" :key="index">
                    <b-td>
                        <color-picker v-model="item.status" :options="options.status.map(item => item.text)"></color-picker>
                    </b-td>
                    <b-td>
                        <b-form-select class="form-control" v-model="item.accommodation" :options="options.accommodation"></b-form-select>
                    </b-td>
                    <b-td>
                        <b-form-input v-model="item.number_of_rooms" type="number"></b-form-input>
                    </b-td>
                    <b-td>
                        <b-form-select class="form-control" v-model="item.room_type" :options="options.room_type"></b-form-select>
                    </b-td>
                    <b-td>
                        <b-form-input v-model="item.check_in_date" type="date"></b-form-input>
                    </b-td>
                    <b-td>
                        <b-form-input v-model="item.nts" type="number"></b-form-input>
                    </b-td>
                    <b-td>
                        <b-form-input v-model="item.price" type="number"></b-form-input>
                    </b-td>
                    <b-td>
                        <b-form-input v-model="item.cost" type="number"></b-form-input>
                    </b-td>
                    <b-td>
                        <b-icon
                            @click="removeItem(index)"
                            icon="x-circle-fill"
                            style="color: #0d6efd;"
                        ></b-icon>
                    </b-td>
                </b-tr>

                <b-tr>
                    <b-td>
                        <b-btn @click="addItem" variant="primary">ADD</b-btn>
                    </b-td>
                    <b-td></b-td>
                    <b-td class="text-center">{{ sumOf('number_of_rooms') }}</b-td>
                    <b-td></b-td>
                    <b-td></b-td>
                    <b-td></b-td>
                    <b-td class="text-center">
                        {{ sumOf('price') }}€
                    </b-td>
                    <b-td class="text-center">
                        {{ sumOf('cost') }}€
                    </b-td>
                    <b-td></b-td>
                </b-tr>
            </b-tbody>
        </b-table-simple>
    </b-container>
</template>

<script>
import ColorPicker from "./ColorPicker";
export default {
    name: "NewTable",
    components: {
        ColorPicker
    },
    data() {
        return {
            options: {
                accommodation: [],
                room_type: [],
                status: []
            },
            items: []
        }
    },
    mounted() {
        this.fetchData()
    },
    watch: {
        items: {
            deep: true,
            handler: (values) => {
                values.map(function (item) {
                    if (item.accommodation !== null && item.number_of_rooms > 0 && item.check_in_date !== null && item.room_type !== null && item.nts > 0) {
                        let a = new Date()
                        let b = new Date(item.check_in_date)
                        const _MS_PER_DAY = 1000 * 60 * 60 * 24;
                        const utc1 = Date.UTC(a.getFullYear(), a.getMonth(), a.getDate());
                        const utc2 = Date.UTC(b.getFullYear(), b.getMonth(), b.getDate());

                        axios.post('/data/save', {
                            data: {
                                acc_id: item.accommodation,
                                number: item.number_of_rooms,
                                'room type': item.room_type,
                                'check in': Math.floor((utc2 - utc1) / _MS_PER_DAY),
                                nights: item.nts
                            }
                        })
                            .then(response => response.data)
                            .then(data => {
                                item.price = data.price
                                item.cost = data.cost
                            })
                    }
                })
            }
        }
    },
    methods: {
        async fetchData() {
            let response = await axios.get('/data/get')

            this.options.accommodation.push({ value: null, text: 'Select an option' })
            for (let i in response.data['Accommodation suppliers']) {
                this.options.accommodation.push({
                    value: response.data['Accommodation suppliers'][i].o_id,
                    text: response.data['Accommodation suppliers'][i].o_name,
                })
            }

            this.options.room_type.push({ value: null, text: 'Select an option' })
            for (let i in response.data['dic_room_type']) {
                this.options.room_type.push({
                    value: response.data['dic_room_type'][i].d_id,
                    text: response.data['dic_room_type'][i].d_name,
                })
            }

            this.options.status.push({ value: null, text: 'Select an option' })
            for (let i in response.data['dic_status']) {
                let colorCode = '#' + response.data['dic_status'][i].d_color_code
                this.options.status.push({
                    value: response.data['dic_status'][i].d_id,
                    text: colorCode,
                })

                this.items.push( this.newItemScheme({ colorCode }) )
            }
        },
        newItemScheme(defaults) {
            return {
                status: defaults && defaults.hasOwnProperty('colorCode') ? defaults.colorCode : '#000',
                accommodation: null,
                number_of_rooms: 0,
                room_type: null,
                check_in_date: null,
                nts: 0,
                price: 0,
                cost: 0
            }
        },
        addItem() {
            this.items.push(
                this.newItemScheme()
            )
        },
        sumOf(t) {
            let s = 0;
            this.items.map(i => s += +i[t])
            return s
        },
        removeItem(index) {
            if (this.items.length === 1) return
            this.items.splice(index, 1)
        }
    },
}
</script>

<style>

</style>
