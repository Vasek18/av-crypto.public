<template>
    <form method="get" ref="form">
        <div class="form-group">
            <label for="currency_pair">Валютная пара</label>
            <select id="currency_pair" name="currency_pair" class="form-control" @input="onSelectPair">
                <option value="">Выберите</option>
                <option v-for="currency_pair in currency_pairs"
                        :value="currency_pair.id"
                        :selected="currency_pair.id == selected_pair_id"
                >
                    Биржа "{{ exchangeMarketsArray[currency_pair.exchange_market_id].name }}" пара "{{
                    currency_pair.currency_1_code }}/{{ currency_pair.currency_2_code }}"
                </option>
            </select>
        </div>
    </form>
</template>
<script>
    export default {
        props: {
            currency_pairs: {},
            exchange_markets: {},
            selected_pair_id: '',
        },

        data: function () {
            return {}
        },

        computed: {
            exchangeMarketsArray: function () {
                let exms = {};
                this.exchange_markets.forEach(function (item) {
                    exms[item.id] = item;
                });
                return exms;
            },
        },

        mounted: function () {
        },

        methods: {
            onSelectPair: function () {
                let vm = this;

                vm.$refs.form.submit();
            },
        }
    }
</script>
<style lang="scss">
</style>