# Image légère
FROM alpine:latest

# Installer bash (optionnel)
RUN apk add --no-cache bash

# Script de test
RUN echo '#!/bin/bash' > /test.sh && \
    echo 'echo "✅ Docker test réussi !"' >> /test.sh && \
    chmod +x /test.sh

# Commande par défaut
CMD ["/test.sh"]
