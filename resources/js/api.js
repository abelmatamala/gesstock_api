import "./bootstrap";

export async function apiFetch(url, options = {}) {
    const token = sessionStorage.getItem("token");

    if (!options.headers) {
        options.headers = {};
    }

    if (token) {
        options.headers["Authorization"] = "Bearer " + token;
    }

    options.headers["Accept"] = "application/json";

    const response = await fetch(url, options);
    if (response.status === 401) {
        console.warn("401 recibido en:", url);

        if (!url.includes("/login")) {
            return response;
        }

        alert("Sesión expirada. Debe volver a iniciar sesión.");
        sessionStorage.clear();
        window.location.href = "/login";
    }
    /*if (response.status === 401) {
        alert("Sesión expirada. Debe volver a iniciar sesión.");
        sessionStorage.clear();
        window.location.href = "/login";
        return null;
    }*/

    return response;
}
