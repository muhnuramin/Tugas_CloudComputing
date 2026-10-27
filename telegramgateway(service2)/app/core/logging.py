from loguru import logger
import sys

logger.remove()
logger.add(sys.stdout, level="INFO", enqueue=True, backtrace=True, diagnose=False, format="{time} | {level} | {message}")
