// used to exit a node process for release tasks

if (process.env.SKIP_BUILD) {
	process.exit(0);
} else {
	process.exit(1);
}
