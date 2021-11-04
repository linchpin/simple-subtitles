const { registerPlugin } = wp.plugins;

import Simple_Subtitles from './post-meta';

registerPlugin( 'simple-subtitles', {
	render() {
		return(<Simple_Subtitles />);
	}
} );
