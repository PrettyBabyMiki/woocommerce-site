/**
 * Internal dependencies
 */
import { getUnreadNotesCount, hasValidNotes } from '../utils';

const notes = [
	{
		id: 1,
		date_created_gmt: '2019-05-10T16:57:31',
		is_deleted: false,
		status: 'unactioned',
	},
	{
		id: 2,
		date_created_gmt: '2020-05-12T16:57:31',
		is_deleted: false,
		status: 'unactioned',
	},
	{
		id: 3,
		date_created_gmt: '2020-05-14T16:57:31',
		is_deleted: false,
		status: 'unactioned',
	},
	{
		id: 4,
		date_created_gmt: '2020-05-15T16:57:31',
		is_deleted: false,
		status: 'unactioned',
	},
	{
		id: 5,
		date_created_gmt: '2020-05-18T16:57:31',
		is_deleted: false,
		status: 'unactioned',
	},
];

describe( 'getUnreadNotesCount', () => {
	const lastRead = 1589285995243;

	test( 'should return 4, 1 of the notes was read', () => {
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 4 );
	} );

	test( 'should return 3, 1 of the notes was read and 1 is deleted', () => {
		notes[ 3 ].is_deleted = true;
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 3 );
	} );

	test( 'should return 2, 2 of the notes were read and 1 is deleted', () => {
		notes[ 1 ].date_created_gmt = '2020-05-05T16:57:31';
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 2 );
	} );

	test( 'should return 1, 2 of the notes were read, 1 was actioned and 1 is deleted', () => {
		notes[ 4 ].status = 'actioned';
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 1 );
	} );

	test( 'should return 0, 2 of the notes were read and 2 are deleted', () => {
		notes[ 2 ].is_deleted = true;
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 0 );
	} );
} );

describe( 'hasValidNotes', () => {
	test( 'should return true, 2 notes are deleted', () => {
		expect( hasValidNotes( notes ) ).toBeTruthy();
	} );
	test( 'should return false, 4 notes are deleted', () => {
		notes[ 0 ].is_deleted = true;
		notes[ 3 ].is_deleted = true;
		expect( hasValidNotes( notes ) ).toBeTruthy();
	} );
} );
