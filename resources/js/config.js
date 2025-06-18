const config = {
    is_idrg: (import.meta.env.VITE_IS_IDRG || "false") === "true", // konversi ke boolean. secra default false (inacbg)
};

export default config;
