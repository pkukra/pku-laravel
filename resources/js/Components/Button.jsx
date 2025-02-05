// Button.jsx
import React from "react";

const Button = ({ onClick, disabled, className, children, loading }) => {
    return (
        <button
            className={`${className}`}
            onClick={onClick}
            disabled={disabled || loading}
        >
            {loading ? (
                <span className="loading loading-spinner loading-md"></span> // Indikator loading
            ) : (
                children
            )}
        </button>
    );
};

export default Button;
