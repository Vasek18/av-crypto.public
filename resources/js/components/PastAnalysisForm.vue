<template>
	<div>
		<div class="row">
			<div class="form-group col-md-4">
				<label for="currency_pair">Валютная пара</label>
				<select id="currency_pair"
				        name="currency_pair"
				        class="form-control"
				        @change="onSelectCurrencyPair"
				        required
				>
					<option value="">Выберите</option>
					<option v-for="currency_pair in currency_pairs"
					        :value="currency_pair.id">Биржа "{{ exchangeMarketsArray[currency_pair.exchange_market_id].name }}" пара "{{ currency_pair.currency_1_code }}/{{ currency_pair.currency_2_code }}"
					</option>
				</select>
			</div>
			<div class="form-group col-md-4"
			     v-if="Object.keys(currency_pair).length">
				<label for="from">С</label>
				<input type="datetime-local"
					   id="from"
					   name="from"
					   class="form-control"
					   :value="dateFrom"
					   @input="changeDateFrom"
					   required
				>
			</div>
			<div class="form-group col-md-4"
			     v-if="Object.keys(currency_pair).length">
				<label for="to">По</label>
				<input type="datetime-local"
					   id="to"
					   name="to"
					   class="form-control"
					   :value="dateTo"
					   @input="changeDateTo"
					   required
				>
			</div>
		</div>
	</div>
</template>
<script>
	export default {
		props: {
			currency_pairs  : {},
			exchange_markets: {},
		},

		data: function(){
			return {
				currency_pair: {},
				dateFrom     : '',
				dateTo       : '',
			}
		},

		mounted: function(){
			let vm = this;

			vm.dateFrom = vm.defaultDateFrom;
			vm.dateTo   = vm.defaultDateTo;

			vm.emitFormChange();
		},

		computed: {
			exchangeMarketsArray: function(){
				let exms = {};
				this.exchange_markets.forEach(function(item){
					exms[item.id] = item;
				});
				return exms;
			},
			defaultDateFrom     : function(){
                return DateTimeHelper.convertTimestampToInputDateTimeFormat(DateTimeHelper.getLastMidnightTimestamp());
			},
			defaultDateTo       : function(){
                return DateTimeHelper.convertTimestampToInputDateTimeFormat(DateTimeHelper.getNextMidnightTimestamp());
			}
		},

		methods: {
			changeDateFrom      : function(event){
				let vm = this;

				vm.dateFrom = event.target.value;

				vm.emitFormChange();
			},
			changeDateTo        : function(event){
				let vm = this;

				vm.dateTo = event.target.value;

				vm.emitFormChange();
			},
			onSelectCurrencyPair: function(event){
				let currencyPairId = event.target.value;

				let vm = this;

				vm.currency_pairs.forEach(function(pair){
					if (pair.id == currencyPairId){
						vm.currency_pair = pair;
					}
				});

				vm.emitFormChange();
			},
			emitFormChange      : function(){
				let vm = this;

				vm.$emit('formChange', {
					dateTo      : vm.dateTo,
					dateFrom    : vm.dateFrom,
					currencyPair: vm.currency_pair,
				});
			},
		},
	}
</script>
<style lang="scss">
</style>