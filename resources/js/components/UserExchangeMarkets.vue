<template>
    <div v-if="exchange_markets.length"
         class="exchange-markets">
        <h2 class="exchange-markets__title">Биржи</h2>
        <div class="exchange-markets__list">
            <a href="#"
               v-for="exchange_market in exchange_markets"
               :class="['card', {'border-success' : exchange_market.id == exchangeMarket.id}, 'exchange-markets__item']"
               @click.prevent="selectExchangeMarket(exchange_market)"
            >
                <img v-if="exchange_market.img_src"
                     class="card-img-top"
                     :src="exchange_market.img_src"
                     :alt="exchange_market.name"
                     :title="exchange_market.name"
                >
                <div v-else
                     class="card-body">
                    <h5 class="card-title">{{ exchange_market.name }}</h5>
                </div>
            </a>
        </div>
        <div class="exchange-markets__body">
            <exchange-market-user-account v-if="userAccount && !showConnectionForm"
                                          :user_account="userAccount"></exchange-market-user-account>
            <exchange-market-connection-form v-if="showConnectionForm"
                                             :exchange_market="exchangeMarket"
                                             @connected="onConnect"
            ></exchange-market-connection-form>
        </div>
    </div>
</template>
<script>
    export default {
        props: {
            exchange_markets: {}
        },
        data: function () {
            return {
                exchangeMarket: {},
                userAccount: false,
                showConnectionForm: false
            }
        },

        mounted: function () {
            let selectedExchangeMarket = this.exchange_markets[0];
            // стараемся выбирать биржу, на которой уже есть подтверждённый аккаунт
            this.exchange_markets.forEach(function (exchange_market) {
                if (exchange_market.accounts.length) {
                    selectedExchangeMarket = exchange_market;
                }
            });

            if (this.exchange_markets.length) {
                this.selectExchangeMarket(selectedExchangeMarket)
            }
        },

        computed: {},

        methods: {
            selectExchangeMarket: function (exchange_market) {
                let vm = this;

                vm.exchangeMarket = exchange_market;

                let account = exchange_market.accounts[0];
                if (account) { // показываем инфу об аккаунте
                    vm.userAccount = {};
                    for (let prop in account) {
                        if (account.hasOwnProperty(prop)) {
                            vm.userAccount[prop] = account[prop];
                        }
                    }
                    vm.showConnectionForm = false;
                }
                else { // показываем форму подключения
                    vm.showConnectionForm = true;
                }
            },

            onConnect: function (account) {
//				this.showConnectionForm = false;
//
//				this.userAccount = account;

                location.reload(); // чтобы не привязывать аккаунт вложенным объектом к бирже
            }
        },
    }
</script>
<style lang="scss">
    .exchange-markets__list {
        display: flex;
        justify-content: space-between;
    }
    .exchange-markets__item {
        width: 18rem;
    }
</style>