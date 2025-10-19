// api/ai-proxy.js - Serverless Function para Vercel

// Esta función es el punto de entrada para todas las peticiones
module.exports = async (req, res) => {
    // ----------------------------------------------------
    // 1. CONFIGURACIÓN DE SEGURIDAD (CORS) Y MÉTODO
    // ----------------------------------------------------
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, api_key'); // api_key para el frontend
    res.setHeader('Content-Type', 'application/json');

    // Manejo de la petición OPTIONS (pre-vuelo CORS)
    if (req.method === 'OPTIONS') {
        res.status(200).end();
        return;
    }

    if (req.method !== 'POST') {
        res.status(405).json({ success: false, response: 'Método no permitido. Use POST.' });
        return;
    }

    // ----------------------------------------------------
    // 2. LECTURA DE DATOS
    // ----------------------------------------------------
    try {
        // Vercel y Express parsean el cuerpo JSON automáticamente
        const { prompt, api_key, provider } = req.body || JSON.parse(req.body);
        
        if (!api_key || !prompt || !provider) {
             return res.status(400).json({ success: false, response: 'Faltan parámetros críticos (API key, prompt, o provider).' });
        }

        let url = '';
        let body = {};
        let headers = { 'Content-Type': 'application/json' };

        // ----------------------------------------------------
        // 3. LÓGICA DE ENDPOINT (CLAUDE vs GEMINI)
        // ----------------------------------------------------
        if (provider === 'claude') {
            url = 'https://api.anthropic.com/v1/messages';
            headers['x-api-key'] = api_key;
            headers['anthropic-version'] = '2023-06-01'; // Versión necesaria
            body = {
                model: 'claude-3-haiku', 
                max_tokens: 1000,
                messages: [{ role: 'user', content: prompt }]
            };
        } else if (provider === 'gemini') {
            // Nota: Aquí la API Key va en la query string
            url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key=${api_key}`;
            body = {
                contents: [{ parts: [{ text: prompt }] }]
            };
        } else {
            return res.status(400).json({ success: false, response: 'Proveedor no soportado.' });
        }

        // ----------------------------------------------------
        // 4. INVOCACIÓN DE LA API DE IA (Fetch)
        // ----------------------------------------------------
        const aiResponse = await fetch(url, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(body)
        });
        
        const aiData = await aiResponse.json();

        if (!aiResponse.ok) {
            // Manejo de errores de la API de IA (ej., 401 Unauthorized)
            const errorMsg = aiData.error?.message || 'Error desconocido de la API de IA';
            return res.status(aiResponse.status).json({ success: false, response: `Error de API (${provider}): ${errorMsg}` });
        }

        // ----------------------------------------------------
        // 5. EXTRACCIÓN Y DEVOLUCIÓN DE LA RESPUESTA
        // ----------------------------------------------------
        let output = '';
        if (provider === 'claude') {
            // Claude devuelve un array de contenido
            output = aiData.content?.[0]?.text || 'Sin respuesta de Claude.';
        } else if (provider === 'gemini') {
            // Gemini devuelve la respuesta en 'candidates'
            output = aiData.candidates?.[0]?.content?.parts?.[0]?.text || 'Sin respuesta de Gemini.';
        }

        res.status(200).json({ success: true, response: output });

    } catch (error) {
        console.error('Backend execution error:', error);
        res.status(500).json({ success: false, response: 'Error interno del servidor proxy.' });
    }
};