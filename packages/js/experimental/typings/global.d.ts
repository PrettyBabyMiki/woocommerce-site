declare global {
	interface Window {
		wcSettings: {
			adminUrl: string;
		};
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
