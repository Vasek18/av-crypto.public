class DateTimeHelper {
    // приведение аргумента к типу Date
    static castToDateObject(date) {
        if (typeof date == 'string') {
            if (isNaN(date)) {
                date = new Date(date);
            } else { // timestamp
                date = Number(date);
            }
        }
        if (typeof date == 'number') { // если вместо даты передали timestamp
            let cDate = date;
            let millisecondsInSecond = 1000;
            date = new Date();
            date.setTime(Number(cDate) * millisecondsInSecond); // setTime принимает миллисекунды
        }

        return date;
    }

    // в формат dd.mm.YY
    static formatDate(date) {
        if (!date) {
            return '';
        }

        date = this.castToDateObject(date);

        let dd = date.getDate();
        if (dd < 10) dd = '0' + dd;
        let mm = date.getMonth() + 1;
        if (mm < 10) mm = '0' + mm;
        let yy = date.getFullYear() % 100;
        if (yy < 10) yy = '0' + yy;

        return dd + '.' + mm + '.' + yy;
    }

    // в формат H:s
    static formatTime(date) {
        if (!date) {
            return '';
        }

        date = this.castToDateObject(date);

        let H = date.getHours();
        let i = date.getMinutes();
        if (i < 10) i = '0' + i;
        let timeString = H + ':' + i;

        return timeString;
    }

    // в формат dd.mm.YY H:s
    static formatDateTime(date) {
        return this.formatDate(date) + ' ' + this.formatTime(date);
    }

    static getDateTimestampFromTimestamp(timestamp) {
        let millisecondsInSecond = 1000;
        let timestampWithMilliseconds = timestamp * millisecondsInSecond;

        let date = new Date(timestampWithMilliseconds);

        date.setHours(0, 0, 0, 0); // сбрасываем часы

        return date.getTime() / millisecondsInSecond;
    }

    static getUserTimeStampOffsetInSeconds() {
        let moscowTimezoneOffset = -180;

        let date = new Date();

        return (date.getTimezoneOffset() - moscowTimezoneOffset) * 60;
    }

    static getTimestampsForDatesBetweenTwoDates(dateFrom, dateTo) {
        let dates = [];
        let secondsInDay = 86400;

        for (let cDate = Number(dateFrom); cDate <= Number(dateTo); cDate += secondsInDay) {
            dates.push(cDate)
        }

        return dates;
    }

    static getTimestampsForHoursAtDate(dateTimestamp) {
        let hours = [];
        let hoursInDay = 24;
        let secondsInHour = 3600;

        for (let i = 0; i < hoursInDay; i++) {
            let hourTimestamp = dateTimestamp + i * secondsInHour;
            hours.push(hourTimestamp);
        }

        return hours;
    }

    static addHourToTimestamp(timestamp) {
        let secondsInHour = 3600;

        return timestamp + secondsInHour;
    }

    static convertTimestampToInputDateFormat(timestamp) {
        if (!timestamp) {
            return '';
        }

        let millisecondsInSecond = 1000;
        let date = new Date();
        date.setTime(Number(timestamp) * millisecondsInSecond); // setTime принимает миллисекунды

        let dd = date.getDate();
        if (dd < 10) dd = '0' + dd;
        let mm = date.getMonth() + 1;
        if (mm < 10) mm = '0' + mm;
        let yyyy = date.getFullYear();

        return yyyy + '-' + mm + '-' + dd;
    }

    static convertTimestampToInputDateTimeFormat(timestamp) {
        if (!timestamp) {
            return '';
        }

        let millisecondsInSecond = 1000;
        let date = new Date();
        date.setTime(Number(timestamp) * millisecondsInSecond); // setTime принимает миллисекунды

        let dd = date.getDate();
        if (dd < 10) dd = '0' + dd;
        let mm = date.getMonth() + 1;
        if (mm < 10) mm = '0' + mm;
        let yyyy = date.getFullYear();
        let H = date.getHours();
        if (H < 10) H = '0' + H;
        let i = date.getMinutes();
        if (i < 10) i = '0' + i;

        return yyyy + '-' + mm + '-' + dd + 'T' + H + ':' + i;
    }

    static getLastMidnightTimestamp() {
        let date = new Date();

        return date.setHours(0, 0, 0, 0) / 1000;
    }

    static getNextMidnightTimestamp() {
        let date = new Date();

        date.setDate(date.getDate() + 1);

        return date.setHours(0, 0, 0, 0) / 1000;
    }

    static getWeekStartTimestamp() {
        let date = new Date();

        let day = date.getDay();
        let dayDifference = date.getDate() - day + (day == 0 ? -6 : 1);
        date.setDate(dayDifference);

        return date.setHours(0, 0, 0, 0) / 1000;
    }

    static getMonthStartTimestamp() {
        let date = new Date();

        date = new Date(date.getFullYear(), date.getMonth(), 1)

        return date.setHours(0, 0, 0, 0) / 1000;
    }

    static getLastMonthStartTimestamp() {
        let date = new Date();

        date = new Date(date.getFullYear(), date.getMonth() - 1, 1)

        return date.setHours(0, 0, 0, 0) / 1000;
    }

    static getYearStartTimestamp() {
        let date = new Date();

        date = new Date(date.getFullYear(), 0, 1)

        return date.setHours(0, 0, 0, 0) / 1000;
    }

    static includeTimezoneOffset(dateString) {
        let date = new Date(dateString);
        let timestamp = date.getTime() / 1000;

        timestamp = timestamp + this.getUserTimeStampOffsetInSeconds();

        return this.convertTimestampToInputDateFormat(timestamp);
    }
}

window.DateTimeHelper = DateTimeHelper;