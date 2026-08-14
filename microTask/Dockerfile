FROM node:16-slim

WORKDIR /home/node/app

COPY package*.json ./
RUN npm install --omit=dev

COPY --chown=node:node ./src ./src

USER node
EXPOSE 3007

CMD ["node", "./src/index.js"]
