`DatePicker` (component)
========================



Props
-----

### `date`

- Type: Object
- Default: null

A moment date object representing the selected date. `null` for no selection.

### `text`

- Type: String
- Default: null

The date in human-readable format. Displayed in the text input.

### `error`

- Type: String
- Default: null

A string error message, shown to the user.

### `invalidDays`

- Type: One of type: enum, func
- Default: null

(Coming Soon) Optionally invalidate certain days. `past`, `future`, `none`, or function are accepted.
A function will be passed to react-dates' `isOutsideRange` prop

### `onUpdate`

- **Required**
- Type: Function
- Default: null

A function called upon selection of a date or input change.

### `dateFormat`

- **Required**
- Type: String
- Default: null

The date format in moment.js-style tokens.

`DateRange` (component)
=======================

This is wrapper for a [react-dates](https://github.com/airbnb/react-dates) powered calendar.

Props
-----

### `after`

- Type: Object
- Default: null

A moment date object representing the selected start. `null` for no selection.

### `afterError`

- Type: String
- Default: null

A string error message, shown to the user.

### `afterText`

- Type: String
- Default: null

The start date in human-readable format. Displayed in the text input.

### `before`

- Type: Object
- Default: null

A moment date object representing the selected end. `null` for no selection.

### `beforeError`

- Type: String
- Default: null

A string error message, shown to the user.

### `beforeText`

- Type: String
- Default: null

The end date in human-readable format. Displayed in the text input.

### `focusedInput`

- Type: String
- Default: null

String identifying which is the currently focused input (start or end).

### `invalidDays`

- Type: One of type: enum, func
- Default: null

Optionally invalidate certain days. `past`, `future`, `none`, or function are accepted.
A function will be passed to react-dates' `isOutsideRange` prop

### `onUpdate`

- **Required**
- Type: Function
- Default: null

A function called upon selection of a date.

### `shortDateFormat`

- **Required**
- Type: String
- Default: null

The date format in moment.js-style tokens.

