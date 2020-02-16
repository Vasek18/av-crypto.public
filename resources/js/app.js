/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

// подключаем вспомогательный класс для работы с датой
require('./DateTimeHelper');

// подключаем вспомогательные функции
require('./functions');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */


Vue.component('user-exchange-markets', require('./components/UserExchangeMarkets.vue'));
Vue.component('exchange-market-connection-form', require('./components/ExchangeMarketConnectionForm.vue'));
Vue.component('exchange-market-user-account', require('./components/ExchangeMarketAserAccount.vue'));
Vue.component('add-basket-form', require('./components/addBasketForm.vue'));
Vue.component('basket', require('./components/Basket.vue'));
Vue.component('rates-graph', require('./components/RatesGraph.vue'));
Vue.component('past-analysis', require('./components/PastAnalysis.vue'));
Vue.component('past-analysis-form', require('./components/PastAnalysisForm.vue'));
Vue.component('past-analysis-graph-block', require('./components/PastAnalysisGraphBlock.vue'));
Vue.component('currency-pair-select', require('./components/CurrencyPairSelect.vue'));
Vue.component('observations-list', require('./components/ObservationsList.vue'));

Vue.filter('formatDate', function (value) {
    return DateTimeHelper.formatDateTime(value)
});

const app = new Vue({
    el: '#app'
});