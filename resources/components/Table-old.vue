<template>
    <div>
        <b-form-group label="Table Options" label-cols-lg="2" v-slot="{ ariaDescribedby }">
            <b-form-checkbox v-model="striped" :aria-describedby="ariaDescribedby" inline>Striped</b-form-checkbox>
            <b-form-checkbox v-model="bordered" :aria-describedby="ariaDescribedby" inline>Bordered</b-form-checkbox>
        </b-form-group>

        <b-table
            :striped="striped"
            :bordered="bordered"
            :items="items"
        ></b-table>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        let result =  axios.get('/data').then(response => {
                return response.data;
            });

        console.log(result);

        return {
            // fields: ['first_name', 'last_name', 'age'],
            items: [
                {
                    status: '<input/>',
                    accommodation: 'Dickerson',
                    number_of_rooms: 12,
                    room_type: 'true',
                    check_in_date: 'Checked',
                    nts: 1,
                    price_$: 8,
                    cost_$: 91,
                },
                {
                    status: 40,
                    accommodation: 'Dickerson',
                    number_of_rooms: 12,
                    room_type: 'true',
                    check_in_date: 'Checked',
                    nts: 1,
                    price_$: 8,
                    cost_$: 91,
                },
            ],
            striped: false,
            bordered: false,
            tableData: axios.get('/data').then(response => {
                console.log(response.data);
                return response.data;
            }),
        }
    },
    mounted() {
        console.log('asd');
    }

}
</script>
