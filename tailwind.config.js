module.exports = {
    mode: "jit",
    content: [
        "./src/**/*.php",
        "./src/**/*.vue",
        "./resources/**/*{js,vue,blade.php}",
    ],
    prefix: "mm-",
    theme: {
        extend: {},
    },
    darkMode: "class",
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/typography"),
    ],
};
