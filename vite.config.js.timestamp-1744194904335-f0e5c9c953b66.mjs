// vite.config.js
import { defineConfig } from "file:///C:/Users/AIA/Downloads/focus-tracker/focus-tracker/node_modules/vite/dist/node/index.js";
import laravel from "file:///C:/Users/AIA/Downloads/focus-tracker/focus-tracker/node_modules/laravel-vite-plugin/dist/index.js";
import path from "path";
import tailwindcss from "file:///C:/Users/AIA/Downloads/focus-tracker/focus-tracker/node_modules/tailwindcss/lib/index.js";
import autoprefixer from "file:///C:/Users/AIA/Downloads/focus-tracker/focus-tracker/node_modules/autoprefixer/lib/autoprefixer.js";
var __vite_injected_original_dirname = "C:\\Users\\AIA\\Downloads\\focus-tracker\\focus-tracker";
var vite_config_default = defineConfig({
  plugins: [
    laravel({
      input: [
        "resources/css/app.css",
        "resources/css/dashboard.css",
        "resources/css/welcome.css",
        "resources/js/app.js",
        "resources/js/welcome.js",
        "resources/js/meeting-room.js",
        "resources/js/teacher-dashboard.js",
        "resources/js/teacher-meeting.js",
        "resources/js/websocket.js",
        "resources/js/bootstrap.js",
        "resources/js/join.js"
      ],
      refresh: true
    })
  ],
  resolve: {
    alias: {
      "@": path.resolve(__vite_injected_original_dirname, "./resources/js"),
      "~": path.resolve(__vite_injected_original_dirname, "./resources"),
      "bootstrap": path.resolve(__vite_injected_original_dirname, "node_modules/bootstrap")
    }
  },
  build: {
    outDir: "public/build",
    assetsDir: "assets",
    manifest: true,
    rollupOptions: {
      output: {
        manualChunks: void 0,
        entryFileNames: "assets/[name].js",
        chunkFileNames: "assets/[name].js",
        assetFileNames: "assets/[name].[ext]"
      }
    }
  },
  css: {
    postcss: {
      plugins: [
        tailwindcss,
        autoprefixer
      ]
    }
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJDOlxcXFxVc2Vyc1xcXFxBSUFcXFxcRG93bmxvYWRzXFxcXGZvY3VzLXRyYWNrZXJcXFxcZm9jdXMtdHJhY2tlclwiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9maWxlbmFtZSA9IFwiQzpcXFxcVXNlcnNcXFxcQUlBXFxcXERvd25sb2Fkc1xcXFxmb2N1cy10cmFja2VyXFxcXGZvY3VzLXRyYWNrZXJcXFxcdml0ZS5jb25maWcuanNcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfaW1wb3J0X21ldGFfdXJsID0gXCJmaWxlOi8vL0M6L1VzZXJzL0FJQS9Eb3dubG9hZHMvZm9jdXMtdHJhY2tlci9mb2N1cy10cmFja2VyL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnIH0gZnJvbSAndml0ZSc7XHJcbmltcG9ydCBsYXJhdmVsIGZyb20gJ2xhcmF2ZWwtdml0ZS1wbHVnaW4nO1xyXG5pbXBvcnQgcGF0aCBmcm9tICdwYXRoJztcclxuaW1wb3J0IHRhaWx3aW5kY3NzIGZyb20gJ3RhaWx3aW5kY3NzJztcclxuaW1wb3J0IGF1dG9wcmVmaXhlciBmcm9tICdhdXRvcHJlZml4ZXInO1xyXG5cclxuZXhwb3J0IGRlZmF1bHQgZGVmaW5lQ29uZmlnKHtcclxuICAgIHBsdWdpbnM6IFtcclxuICAgICAgICBsYXJhdmVsKHtcclxuICAgICAgICAgICAgaW5wdXQ6IFtcclxuICAgICAgICAgICAgICAgICdyZXNvdXJjZXMvY3NzL2FwcC5jc3MnLFxyXG4gICAgICAgICAgICAgICAgJ3Jlc291cmNlcy9jc3MvZGFzaGJvYXJkLmNzcycsXHJcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2Nzcy93ZWxjb21lLmNzcycsXHJcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2pzL2FwcC5qcycsXHJcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2pzL3dlbGNvbWUuanMnLFxyXG4gICAgICAgICAgICAgICAgJ3Jlc291cmNlcy9qcy9tZWV0aW5nLXJvb20uanMnLFxyXG4gICAgICAgICAgICAgICAgJ3Jlc291cmNlcy9qcy90ZWFjaGVyLWRhc2hib2FyZC5qcycsXHJcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2pzL3RlYWNoZXItbWVldGluZy5qcycsXHJcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2pzL3dlYnNvY2tldC5qcycsXHJcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2pzL2Jvb3RzdHJhcC5qcycsXHJcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2pzL2pvaW4uanMnXHJcbiAgICAgICAgICAgIF0sXHJcbiAgICAgICAgICAgIHJlZnJlc2g6IHRydWUsXHJcbiAgICAgICAgfSksXHJcbiAgICBdLFxyXG4gICAgcmVzb2x2ZToge1xyXG4gICAgICAgIGFsaWFzOiB7XHJcbiAgICAgICAgICAgICdAJzogcGF0aC5yZXNvbHZlKF9fZGlybmFtZSwgJy4vcmVzb3VyY2VzL2pzJyksXHJcbiAgICAgICAgICAgICd+JzogcGF0aC5yZXNvbHZlKF9fZGlybmFtZSwgJy4vcmVzb3VyY2VzJyksXHJcbiAgICAgICAgICAgICdib290c3RyYXAnOiBwYXRoLnJlc29sdmUoX19kaXJuYW1lLCAnbm9kZV9tb2R1bGVzL2Jvb3RzdHJhcCcpLFxyXG4gICAgICAgIH0sXHJcbiAgICB9LFxyXG4gICAgYnVpbGQ6IHtcclxuICAgICAgICBvdXREaXI6ICdwdWJsaWMvYnVpbGQnLFxyXG4gICAgICAgIGFzc2V0c0RpcjogJ2Fzc2V0cycsXHJcbiAgICAgICAgbWFuaWZlc3Q6IHRydWUsXHJcbiAgICAgICAgcm9sbHVwT3B0aW9uczoge1xyXG4gICAgICAgICAgICBvdXRwdXQ6IHtcclxuICAgICAgICAgICAgICAgIG1hbnVhbENodW5rczogdW5kZWZpbmVkLFxyXG4gICAgICAgICAgICAgICAgZW50cnlGaWxlTmFtZXM6ICdhc3NldHMvW25hbWVdLmpzJyxcclxuICAgICAgICAgICAgICAgIGNodW5rRmlsZU5hbWVzOiAnYXNzZXRzL1tuYW1lXS5qcycsXHJcbiAgICAgICAgICAgICAgICBhc3NldEZpbGVOYW1lczogJ2Fzc2V0cy9bbmFtZV0uW2V4dF0nXHJcbiAgICAgICAgICAgIH0sXHJcbiAgICAgICAgfSxcclxuICAgIH0sXHJcbiAgICBjc3M6IHtcclxuICAgICAgICBwb3N0Y3NzOiB7XHJcbiAgICAgICAgICAgIHBsdWdpbnM6IFtcclxuICAgICAgICAgICAgICAgIHRhaWx3aW5kY3NzLFxyXG4gICAgICAgICAgICAgICAgYXV0b3ByZWZpeGVyLFxyXG4gICAgICAgICAgICBdLFxyXG4gICAgICAgIH0sXHJcbiAgICB9LFxyXG59KTtcclxuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUFvVixTQUFTLG9CQUFvQjtBQUNqWCxPQUFPLGFBQWE7QUFDcEIsT0FBTyxVQUFVO0FBQ2pCLE9BQU8saUJBQWlCO0FBQ3hCLE9BQU8sa0JBQWtCO0FBSnpCLElBQU0sbUNBQW1DO0FBTXpDLElBQU8sc0JBQVEsYUFBYTtBQUFBLEVBQ3hCLFNBQVM7QUFBQSxJQUNMLFFBQVE7QUFBQSxNQUNKLE9BQU87QUFBQSxRQUNIO0FBQUEsUUFDQTtBQUFBLFFBQ0E7QUFBQSxRQUNBO0FBQUEsUUFDQTtBQUFBLFFBQ0E7QUFBQSxRQUNBO0FBQUEsUUFDQTtBQUFBLFFBQ0E7QUFBQSxRQUNBO0FBQUEsUUFDQTtBQUFBLE1BQ0o7QUFBQSxNQUNBLFNBQVM7QUFBQSxJQUNiLENBQUM7QUFBQSxFQUNMO0FBQUEsRUFDQSxTQUFTO0FBQUEsSUFDTCxPQUFPO0FBQUEsTUFDSCxLQUFLLEtBQUssUUFBUSxrQ0FBVyxnQkFBZ0I7QUFBQSxNQUM3QyxLQUFLLEtBQUssUUFBUSxrQ0FBVyxhQUFhO0FBQUEsTUFDMUMsYUFBYSxLQUFLLFFBQVEsa0NBQVcsd0JBQXdCO0FBQUEsSUFDakU7QUFBQSxFQUNKO0FBQUEsRUFDQSxPQUFPO0FBQUEsSUFDSCxRQUFRO0FBQUEsSUFDUixXQUFXO0FBQUEsSUFDWCxVQUFVO0FBQUEsSUFDVixlQUFlO0FBQUEsTUFDWCxRQUFRO0FBQUEsUUFDSixjQUFjO0FBQUEsUUFDZCxnQkFBZ0I7QUFBQSxRQUNoQixnQkFBZ0I7QUFBQSxRQUNoQixnQkFBZ0I7QUFBQSxNQUNwQjtBQUFBLElBQ0o7QUFBQSxFQUNKO0FBQUEsRUFDQSxLQUFLO0FBQUEsSUFDRCxTQUFTO0FBQUEsTUFDTCxTQUFTO0FBQUEsUUFDTDtBQUFBLFFBQ0E7QUFBQSxNQUNKO0FBQUEsSUFDSjtBQUFBLEVBQ0o7QUFDSixDQUFDOyIsCiAgIm5hbWVzIjogW10KfQo=
