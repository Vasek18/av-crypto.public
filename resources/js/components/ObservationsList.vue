<template>
    <div class="observations row">
        <div class="observation-list col-md-9">
            <div class="observation" v-for="observation in sortedObservations" v-if="showObservation(observation)">
                <h2>{{ observation.event_code }}</h2>
                <div>
                    <dl>
                        <template v-for="(value, code) in observation.params">
                            <dt>{{ code }}</dt>
                            <dd>{{ value }}</dd>
                        </template>
                    </dl>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="jumbotron">
                            <h3 class="display-4">{{ observation.top_hits ? observation.top_hits: 0 }}
                                <small v-if="observation.top_hits">({{ observation.top_hits_percent }}%)</small>
                            </h3>
                            <p>Цена увеличилась на {{ percent }}%</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="jumbotron">
                            <h3 class="display-4">{{ observation.bottom_hits ? observation.bottom_hits: 0 }}
                                <small v-if="observation.bottom_hits">({{ observation.bottom_hits_percent }}%)</small>
                            </h3>
                            <p>Цена упала на {{ percent }}%</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="jumbotron">
                            <h3 class="display-4">{{ observation.missed ? observation.missed: 0 }}
                                <small v-if="observation.missed">({{ observation.missed_percent }}%)</small>
                            </h3>
                            <p>Цена не изменилась на {{ percent }}%</p>
                        </div>
                    </div>
                </div>
                <hr>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="min_count">Минимальное количество наблюдений</label>
                <input type="number" name="min_count" id="min_count" class="form-control"
                       @input="onMinObservationsCountChange" :value="minObservationCount">
            </div>
            <div>
                <label>Тип наблюдения</label>
                <div class="list-group" v-if="sortedCodes.length">
                    <a href="#"
                       class="list-group-item"
                       :class="{'active': chosenEventCodes.includes(eventCode)}"
                       v-for="eventCode in sortedCodes" @click.prevent="codeSelect(eventCode)">{{
                        eventCode }}</a>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        props: {
            observations: {},
            percent: {},
        },

        data: function () {
            return {
                observationsArray: [],
                eventCodes: [],
                chosenEventCodes: [],
                minObservationCount: 10,
            }
        },

        mounted: function () {
            let vm = this;
            let observations = [];

            // собираем типы событий и события в массив
            for (let i in this.observations) {
                let observation = vm.observations[i];

                // собираем события и сразу высчитываем процентное соотношение результатов
                observation.top_hits_percent = Math.round(observation.top_hits * 100 / (observation.top_hits + observation.bottom_hits + observation.missed));
                observation.bottom_hits_percent = Math.round(observation.bottom_hits * 100 / (observation.top_hits + observation.bottom_hits + observation.missed));
                observation.missed_percent = Math.round(observation.missed * 100 / (observation.top_hits + observation.bottom_hits + observation.missed));
                observations.push(observation);

                // собираем типы событий
                if (!vm.eventCodes.includes(observation.event_code)) {
                    vm.eventCodes.push(observation.event_code);
                }
            }

            vm.observationsArray = observations;
        },

        computed: {
            sortedCodes: function () {
                return this.eventCodes.sort();
            },
            sortedObservations: function () {
                return this.observationsArray.sort(function (a, b) {
                    if (a.top_hits_percent == b.top_hits_percent) {
                        if (a.bottom_hits_percent == b.bottom_hits_percent) {
                            return a.missed < b.missed ? 1 : -1;
                        }

                        return a.bottom_hits_percent > b.bottom_hits_percent ? 1 : -1;
                    }

                    return a.top_hits_percent < b.top_hits_percent ? 1 : -1;
                });
            }
        },

        watch: {},

        methods: {
            codeSelect: function (code) {
                let vm = this;
                if (vm.chosenEventCodes.includes(code)) {
                    vm.chosenEventCodes.splice(vm.chosenEventCodes.indexOf(code), 1);
                } else {
                    vm.chosenEventCodes.push(code)
                }
            },
            onMinObservationsCountChange: function (event) {
                this.minObservationCount = event.target.value;
            },
            showObservation: function (observation) {
                let vm = this;
                if (vm.chosenEventCodes.length) {
                    if (vm.chosenEventCodes.indexOf(observation.event_code) === -1) {
                        return false;
                    }
                }
                if (vm.minObservationCount) {
                    if (observation.top_hits + observation.bottom_hits + observation.missed < vm.minObservationCount) {
                        return false;
                    }
                }
                return true;
            }
        },
    }
</script>
<style lang="scss">
</style>