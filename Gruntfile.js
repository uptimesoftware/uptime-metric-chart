module.exports = function(grunt) {
	grunt.loadNpmTasks('uptime-gadget-tasks');

	grunt.registerTask('default', 'uptime-gadget:compress');
};
