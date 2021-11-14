<template>
    <div>
        <div class="color-picker">
            <ul class="selected">
                <li @click="proxyShow = !proxyShow" class="item" :style="{ backgroundColor: value }"></li>
            </ul>
            <ul v-if="proxyShow" class="options">
                <li @click="proxyValue = color,proxyShow = false" class="item" v-for="(color, index) in options" :style="{ backgroundColor: color }" :key="index"></li>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    name: "ColorPicker",
    props: ['show', 'value', 'options'],
    data() {
        return {
            proxyShow: this.show,
            proxyValue: null
        }
    },
    watch: {
        proxyValue() {
            this.$emit('input', this.proxyValue)
        }
    }
}
</script>

<style scoped>
    .color-picker {
        position: relative;
    }
    .color-picker .selected, .color-picker .options {
        list-style-type: none;
    }
    .color-picker .options .item, .color-picker .selected .item {
        width: 38px;
        height: 38px;
    }
    .color-picker .selected {
        all: unset;
    }
    .color-picker .selected .item {
        border-radius: 0.25rem;
    }
    .color-picker .options {
        display: flex;
        position: absolute;
        background-color: white;
        padding: 4px;
        border-radius: 0.25rem;
        margin-top: 4px;
        z-index: 1;
    }
    .color-picker .options .item {
        margin-right: 2px;
    }
</style>
