cliente -> [
    uri -> [caminho solicitado]
    metodo -> [get, post, put, delete],
    dados -> [dados do serviço]
]

servidor -> [
    status -> bool,
    formato-resposta -> [json ou html],
    resposta -> string com a resposta ou com a pagina a ser chamada
]