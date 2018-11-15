/** @format */

/**
 * External dependencies
 */
import classNames from 'classnames';
import PropTypes from 'prop-types';
import { Component, createRef } from '@wordpress/element';
import { isEmpty, isEqual } from 'lodash';
import { select as d3Select } from 'd3-selection';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Provides foundation to use D3 within React.
 *
 * React is responsible for determining when a chart should be updated (e.g. whenever data changes or the browser is
 * resized), while D3 is responsible for the actual rendering of the chart (which is performed via DOM operations that
 * happen outside of React's control).
 *
 * This component makes use of new lifecycle methods that come with React 16.3. Thus, while this component (i.e. the
 * container of the chart) is rendered during the 'render phase' the chart itself is only rendered during the 'commit
 * phase' (i.e. in 'componentDidMount' and 'componentDidUpdate' methods).
 */
export default class D3Base extends Component {
	static propTypes = {
		className: PropTypes.string,
		data: PropTypes.any, // required to detect changes in data
		drawChart: PropTypes.func.isRequired,
		getParams: PropTypes.func.isRequired,
		type: PropTypes.string,
	};

	state = {
		data: null,
		params: null,
		drawChart: null,
		getParams: null,
		type: null,
	};

	chartRef = createRef();

	static getDerivedStateFromProps( nextProps, prevState ) {
		let state = {};

		if ( ! isEqual( nextProps.data, prevState.data ) ) {
			state = { ...state, data: nextProps.data };
		}

		if ( ! isEqual( nextProps.drawChart, prevState.drawChart ) ) {
			state = { ...state, drawChart: nextProps.drawChart };
		}

		if ( ! isEqual( nextProps.getParams, prevState.getParams ) ) {
			state = { ...state, getParams: nextProps.getParams };
		}

		if ( nextProps.type !== prevState.type ) {
			state = { ...state, type: nextProps.type };
		}

		if ( ! isEmpty( state ) ) {
			return { ...state, params: null };
		}

		return null;
	}

	componentDidMount() {
		window.addEventListener( 'resize', this.updateParams );

		this.drawChart();
	}

	shouldComponentUpdate( nextProps, nextState ) {
		return (
			( nextState.params !== null && ! isEqual( this.state.params, nextState.params ) ) ||
			! isEqual( this.state.data, nextState.data ) ||
			this.state.type !== nextState.type
		);
	}

	componentDidUpdate() {
		this.drawChart();
	}

	componentWillUnmount() {
		window.removeEventListener( 'resize', this.updateParams );

		this.deleteChart();
	}

	deleteChart() {
		d3Select( this.chartRef.current )
			.selectAll( 'svg' )
			.remove();
	}

	/**
	 * Renders the chart, or triggers a rendering by updating the list of params.
	 */
	drawChart() {
		if ( ! this.state.params ) {
			this.updateParams();
			return;
		}

		const svg = this.getContainer();
		this.props.drawChart( svg, this.state.params );
	}

	getContainer() {
		const { className } = this.props;
		const { width, height } = this.state.params;

		this.deleteChart();

		const svg = d3Select( this.chartRef.current )
			.append( 'svg' )
			.attr( 'viewBox', `0 0 ${ width } ${ height }` )
			.attr( 'height', height )
			.attr( 'width', width )
			.attr( 'preserveAspectRatio', 'xMidYMid meet' );

		if ( className ) {
			svg.attr( 'class', `${ className }__viewbox` );
		}

		return svg.append( 'g' );
	}

	updateParams = () => {
		const params = this.state.getParams( this.chartRef.current );
		this.setState( { params } );
	};

	render() {
		return (
			<div className={ classNames( 'd3-base', this.props.className ) } ref={ this.chartRef } />
		);
	}
}
