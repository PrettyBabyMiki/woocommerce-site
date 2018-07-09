/** @format */

/**
 * External dependencies
 */

import { max as d3Max, range as d3Range } from 'd3-array';
import { axisBottom as d3AxisBottom, axisLeft as d3AxisLeft } from 'd3-axis';
import { format as d3Format } from 'd3-format';
import {
	scaleBand as d3ScaleBand,
	scaleLinear as d3ScaleLinear,
	scaleOrdinal as d3ScaleOrdinal,
	scaleTime as d3ScaleTime,
} from 'd3-scale';
import { mouse as d3Mouse, select as d3Select } from 'd3-selection';
import { line as d3Line } from 'd3-shape';
import { interpolateViridis as d3InterpolateViridis } from 'd3-scale-chromatic';
import { timeFormat as d3TimeFormat, utcParse as d3UTCParse } from 'd3-time-format';

export const parseDate = d3UTCParse( '%Y-%m-%d' );

export const getUniqueKeys = data => {
	return [
		...new Set(
			data.reduce( ( accum, curr ) => {
				Object.keys( curr ).forEach( key => key !== 'date' && accum.push( key ) );
				return accum;
			}, [] )
		),
	];
};

export const getOrderedKeys = ( data, uniqueKeys ) =>
	uniqueKeys
		.map( key => ( {
			key,
			total: data.reduce( ( a, c ) => a + c[ key ], 0 ),
		} ) )
		.sort( ( a, b ) => b.total - a.total )
		.map( d => d.key );

export const getLineData = ( data, orderedKeys ) =>
	orderedKeys.map( key => ( {
		key,
		values: data.map( d => ( {
			date: d.date,
			value: d[ key ],
		} ) ),
	} ) );

export const getUniqueDates = lineData => {
	return [
		...new Set(
			lineData.reduce( ( accum, { values } ) => {
				values.forEach( ( { date } ) => accum.push( date ) );
				return accum;
			}, [] )
		),
	].sort( ( a, b ) => parseDate( a ) - parseDate( b ) );
};

export const getXScale = ( uniqueDates, width ) =>
	d3ScaleBand()
		.domain( uniqueDates )
		.rangeRound( [ 0, width ] )
		.paddingInner( 0.1 );

export const getXGroupScale = ( orderedKeys, xScale ) =>
	d3ScaleBand()
		.domain( orderedKeys )
		.rangeRound( [ 0, xScale.bandwidth() ] )
		.padding( 0.07 );

export const getXLineScale = ( uniqueDates, width ) =>
	d3ScaleTime()
		.domain( [ new Date( uniqueDates[ 0 ] ), new Date( uniqueDates[ uniqueDates.length - 1 ] ) ] )
		.rangeRound( [ 0, width ] );

export const getYMax = lineData =>
	Math.round( 4 / 3 * d3Max( lineData, d => d3Max( d.values.map( date => date.value ) ) ) );

export const getYScale = ( height, yMax ) =>
	d3ScaleLinear()
		.domain( [ 0, yMax ] )
		.rangeRound( [ height, 0 ] );

export const getYTickOffset = ( height, scale, yMax ) =>
	d3ScaleLinear()
		.domain( [ 0, yMax ] )
		.rangeRound( [ height + scale * 12, scale * 12 ] );

export const getColorScale = orderedKeys =>
	d3ScaleOrdinal().range( d3Range( 0, 1.1, 100 / ( orderedKeys.length - 1 ) / 100 ) );

export const getLine = ( data, xLineScale, yScale ) =>
	d3Line()
		.x( d => xLineScale( new Date( d.date ) ) )
		.y( d => yScale( d.value ) );

export const getDateSpaces = ( uniqueDates, width, xLineScale ) =>
	uniqueDates.map( ( d, i ) => {
		const xNow = xLineScale( new Date( d ) );
		const xPrev =
			i >= 1
				? xLineScale( new Date( uniqueDates[ i - 1 ] ) )
				: xLineScale( new Date( uniqueDates[ 0 ] ) );
		const xNext =
			i < uniqueDates.length - 1
				? xLineScale( new Date( uniqueDates[ i + 1 ] ) )
				: xLineScale( new Date( uniqueDates[ uniqueDates.length - 1 ] ) );
		let xWidth = i === 0 ? xNext - xNow : xNow - xPrev;
		const xStart = i === 0 ? 0 : xNow - xWidth / 2;
		xWidth = i === 0 || i === uniqueDates.length - 1 ? xWidth / 2 : xWidth;
		return {
			date: d,
			start: uniqueDates.length > 1 ? xStart : 0,
			width: uniqueDates.length > 1 ? xWidth : width,
		};
	} );

export const drawAxis = ( node, data, params ) => {
	const xScale = params.type === 'line' ? params.xLineScale : params.xScale;

	const yGrids = [];
	for ( let i = 0; i < 4; i++ ) {
		yGrids.push( i / 3 * params.yMax );
	}
	node
		.append( 'g' )
		.attr( 'class', 'axis' )
		.attr( 'transform', `translate(0,${ params.height })` )
		.call(
			d3AxisBottom( xScale )
				.tickValues( params.uniqueDates.map( d => ( params.type === 'line' ? new Date( d ) : d ) ) )
				.tickFormat( d => d3TimeFormat( '%d' )( d instanceof Date ? d : new Date( d ) ) )
		);

	node
		.append( 'g' )
		.attr( 'class', 'grid' )
		.attr( 'transform', `translate(-${ params.margin.left },0)` )
		.call(
			d3AxisLeft( params.yScale )
				.tickValues( yGrids )
				.tickSize( -params.width - params.margin.left )
				.tickFormat( '' )
		)
		.call( g => g.select( '.domain' ).remove() );

	node
		.append( 'g' )
		.attr( 'class', 'axis y-axis' )
		.call(
			d3AxisLeft( params.yTickOffset )
				.tickValues( yGrids )
				.tickFormat( d => ( d !== 0 ? d3Format( '.3s' )( d ) : 0 ) )
		);

	node
		.selectAll( '.y-axis .tick text' )
		.style( 'font-size', `${ Math.round( params.scale * 10 ) }px` );

	node.selectAll( '.domain' ).remove();
	node
		.selectAll( '.axis' )
		.selectAll( '.tick' )
		.select( 'line' )
		.remove();
};

const showTooltip = ( node, params, d ) => {
	const chartCoords = node.node().getBoundingClientRect();
	let [ xPosition, yPosition ] = d3Mouse( node.node() );
	xPosition = xPosition > chartCoords.width - 200 ? xPosition - 200 : xPosition + 20;
	yPosition = yPosition > chartCoords.height - 150 ? yPosition - 200 : yPosition + 20;
	const keys = params.orderedKeys.map(
		( key, i ) => `
		<li>
			<span class="key-colour" style="background-color:${ d3InterpolateViridis(
				params.colorScale( i )
			) }"></span>
			<span class="key-key">${ key }:</span>
			<span class="key-value">${ d3Format( ',.0f' )( d[ key ] ) }</span>
		</li>`
	);
	node
		.select( '.tooltip' )
		.style( 'left', xPosition + 'px' )
		.style( 'top', yPosition + 'px' )
		.style( 'display', 'inline-block' ).html( `
			<div>
				<h4>${ d.date }</h4>
				<ul>
				${ keys.join( '' ) }
				</ul>
			</div>
		` );
};

const handleMouseOverBarChart = ( d, i, nodes, node, data, params ) => {
	d3Select( nodes[ i ].parentNode )
		.select( '.barfocus' )
		.attr( 'opacity', '0.1' );
	showTooltip( node, params, d );
};

const handleMouseOutBarChart = ( d, i, nodes, node ) => {
	d3Select( nodes[ i ].parentNode )
		.select( '.barfocus' )
		.attr( 'opacity', '0' );
	node.select( '.tooltip' ).style( 'display', 'none' );
};

const handleMouseOverLineChart = ( d, i, nodes, node, data, params ) => {
	d3Select( nodes[ i ].parentNode )
		.select( '.focus-grid' )
		.attr( 'opacity', '1' );
	showTooltip( node, params, data.find( e => e.date === d.date ) );
};

const handleMouseOutLineChart = ( d, i, nodes, node ) => {
	d3Select( nodes[ i ].parentNode )
		.select( '.focus-grid' )
		.attr( 'opacity', '0' );
	node.select( '.tooltip' ).style( 'display', 'none' );
};

export const drawLines = ( node, data, params ) => {
	const g = node
		.select( 'svg' )
		.select( 'g' )
		.select( 'g' )
		.append( 'g' );

	const focus = g
		.selectAll( '.focus-space' )
		.data( params.dateSpaces )
		.enter()
		.append( 'g' )
		.attr( 'class', 'focus-space' );

	focus
		.append( 'line' )
		.attr( 'class', 'focus-grid' )
		.style( 'stroke', 'lightgray' )
		.style( 'stroke-width', 1 )
		.attr( 'x1', d => params.xLineScale( new Date( d.date ) ) )
		.attr( 'y1', 0 )
		.attr( 'x2', d => params.xLineScale( new Date( d.date ) ) )
		.attr( 'y2', params.height )
		.attr( 'opacity', '0' );

	focus
		.append( 'rect' )
		.attr( 'class', 'focus-g' )
		.attr( 'x', d => d.start )
		.attr( 'y', 0 )
		.attr( 'width', d => d.width )
		.attr( 'height', params.height )
		.attr( 'opacity', 0 )
		.on( 'mouseover', ( d, i, nodes ) =>
			handleMouseOverLineChart( d, i, nodes, node, data, params )
		)
		.on( 'mouseout', ( d, i, nodes ) => handleMouseOutLineChart( d, i, nodes, node ) );

	const series = g
		.selectAll( '.line-g' )
		.data( params.lineData )
		.enter()
		.append( 'g' )
		.attr( 'class', 'line-g' );

	series
		.append( 'path' )
		.attr( 'fill', 'none' )
		.attr( 'stroke-width', 3 )
		.attr( 'stroke-linejoin', 'round' )
		.attr( 'stroke-linecap', 'round' )
		.attr( 'stroke', ( d, i ) => d3InterpolateViridis( params.colorScale( i ) ) )
		.attr( 'd', d => params.line( d.values ) );

	series
		.selectAll( 'circle' )
		.data( ( d, i ) => d.values.map( row => ( { ...row, i } ) ) )
		.enter()
		.append( 'circle' )
		.attr( 'r', 3.5 )
		.attr( 'fill', '#fff' )
		.attr( 'stroke', d => d3InterpolateViridis( params.colorScale( d.i ) ) )
		.attr( 'stroke-width', 3 )
		.attr( 'cx', d => params.xLineScale( new Date( d.date ) ) )
		.attr( 'cy', d => params.yScale( d.value ) );
};

export const drawBars = ( node, data, params ) => {
	const barGroup = node
		.select( 'svg' )
		.select( 'g' )
		.select( 'g' )
		.append( 'g' )
		.attr( 'class', 'bars' )
		.selectAll( 'g' )
		.data( data )
		.enter()
		.append( 'g' )
		.attr( 'transform', d => `translate(${ params.xScale( d.date ) },0)` )
		.attr( 'class', 'bargroup' );

	barGroup
		.append( 'rect' )
		.attr( 'class', 'barfocus' )
		.attr( 'x', 0 )
		.attr( 'y', 0 )
		.attr( 'width', params.xGroupScale.range()[ 1 ] )
		.attr( 'height', params.height )
		.attr( 'opacity', '0' );

	barGroup
		.selectAll( '.bar' )
		.data( d => params.orderedKeys.map( key => ( { key: key, value: d[ key ] } ) ) )
		.enter()
		.append( 'rect' )
		.attr( 'class', 'bar' )
		.attr( 'x', d => params.xGroupScale( d.key ) )
		.attr( 'y', d => params.yScale( d.value ) )
		.attr( 'width', params.xGroupScale.bandwidth() )
		.attr( 'height', d => params.height - params.yScale( d.value ) )
		.attr( 'fill', ( d, i ) => d3InterpolateViridis( params.colorScale( i ) ) );

	barGroup
		.append( 'rect' )
		.attr( 'class', 'barmouse' )
		.attr( 'x', 0 )
		.attr( 'y', 0 )
		.attr( 'width', params.xGroupScale.range()[ 1 ] )
		.attr( 'height', params.height )
		.attr( 'opacity', '0' )
		.on( 'mouseover', ( d, i, nodes ) =>
			handleMouseOverBarChart( d, i, nodes, node, data, params )
		)
		.on( 'mouseout', ( d, i, nodes ) => handleMouseOutBarChart( d, i, nodes, node ) );
};
