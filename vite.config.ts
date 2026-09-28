import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'path'

export default createAppConfig(
	{
		main: resolve(join('src', 'main.ts')),
	},
	{
		createEmptyCSSEntryPoints: true,
		extractLicenseInformation: true,
		thirdPartyLicense: false,
		build: {
			rollupOptions: {
				output: {
					// Use .js extension for all output files
					entryFileNames: '[name].js',
					chunkFileNames: '[name].js',
					assetFileNames: '[name].[ext]',
				},
			},
		},
	},
)
